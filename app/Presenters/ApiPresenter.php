<?php
declare(strict_types=1);

namespace App\Presenters;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type"); 

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type');
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header('HTTP/2.0 200 OK');
    die();
}

use Nette;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Utils\DateTime;  //jedna se o tridu se statickymi metodami, tak ji nemusim vstrikovat do konstruktoru
use App\Model\NarozeninyManager;



class ApiPresenter extends Nette\Application\UI\Presenter
{

    public function __construct(
        private Request $request,
        private Response $response,
        private NarozeninyManager $narozeninyManager
    )
    {}



    public function renderDefault(){
        $metodaPozadavku = $this->request->getMethod();
        switch ($metodaPozadavku) {
            case "GET":
                $this->vratNarozeniny();
                break; 
            case "POST":
                $this->ulozOsobu();
                break; 
            case "PUT":
                $this->upravOsobu();
                break; 
            case "DELETE":
                $this->odstranOsobu();
                break; 
            default: $this->odkazNaDokumentaci();

        }

    }

    public function vratNarozeniny(){
        $getparametry = $this->request->getQuery(); //obsahuje pole get parametrů
        if ($getparametry){
            //jsou poslany nejake parametry
            $vysledek = $this->narozeninyManager->vratOsobu($getparametry);
        }else{ //nebyly zadany parametry
            $vysledek = $this->narozeninyManager->vratOsoby();
        }
        $this->sendJson($vysledek);
        exit();

    }

    public function renderChat(){
        $metodaPozadavku = $this->request->getMethod();
        $getparametry = $this->request->getQuery();
        if ($metodaPozadavku == 'GET' && isset($getparametry["messageId"])){
            $vsechnyOdpovedi = $this->narozeninyManager->vratVsechnyOdpovedi((int)$getparametry["messageId"]);
            $this->sendJson($vsechnyOdpovedi);
            exit();
        } else{
            return $this->odkazNaDokumentaci();
            exit();
        }
    }

    public function ulozOsobu(){
       // Získání surových POST dat - pozadavek je typu JSON - je potreba ho prevest na asociativni pole se kterym si Nette trida Request poradi a lepe se s nim pracuje
       $json = file_get_contents('php://input');
       // Převod JSON na asociativní pole
       $getparametry = json_decode($json, true);
    
        $vysledek = $this->narozeninyManager->ulozOsobu($getparametry);
        $this->sendJson($vysledek);
        exit();
    } 

    public function odstranOsobu(){
        // Získání surových DELETE dat - pozadavek je typu JSON - je potreba ho prevest na asociativni pole se kterym si Nette trida Request poradi a lepe se s nim pracuje
       $json = file_get_contents('php://input');
       // Převod JSON na asociativní pole
       $getparametry = json_decode($json, true);
        $vysledek = $this->narozeninyManager->odstranOsobu($getparametry);
        $this->sendJson($vysledek);
        exit();
    }

    public function upravOsobu(){
        // Získání surových PUT dat - pozadavek je typu JSON - je potreba ho prevest na asociativni pole se kterym si Nette trida Request poradi a lepe se s nim pracuje
       $json = file_get_contents('php://input');
       // Převod JSON na asociativní pole
       $getparametry = json_decode($json, true);
        $vysledek = $this->narozeninyManager->upravOsobu($getparametry);
        $this->sendJson($vysledek);
        exit();
    }

    public function odkazNaDokumentaci(){
        $vysledek = $this->narozeninyManager->vratInfoProNevalidniEndpointy();
        $this->sendJson($vysledek);
    }

}
