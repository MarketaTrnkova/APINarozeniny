<?php

namespace App\Model;

use Nette;
use Nette\Database\Explorer;
use Nette\Http\Request;
use Nette\Utils\DateTime;  //jedna se o tridu se statickymi metodami, tak ji nemusim vstrikovat do konstruktoru pomoci DI, dedi z PHP tridy DateTime


class NarozeninyManager{

    private array $nevalidniData = [
        "success" => false,
        "message" => "Požadavek nebyl ve správném formátu, zkontrolujte formát http požadavku"
    ];

    private array $dataNenalezena = [
        "success" => false,
        "message" => "Vybraná osoba nebyla nalezena"
    ];

    private array $chyba = [
        "success" => false,
        "message" => "Nepodařilo se vykonat požadavek.",
        "error" => "" // sem se vlozi chybova hlaska z Nette\Database\UniqueConstraintViolationException
    ];


    public function __construct(
        private Request $request,
        private Explorer $explorer
    )
    {}

    private function vypocitejVek(string $datumNarozeni){
        $datumNarozeni = new DateTime($datumNarozeni);
        $aktualniDatum = new DateTime();
        $vek = $datumNarozeni->diff($aktualniDatum);
        return $vek->y;
    }
 
    //ve vsech funkci je navratova hodnota asociativni pole, ktere presenter automaticky prevede na JSON diky pouziti metody sendJSON()

    public function vratOsobu(array $getparametry){
        //kontrola, zda je nastaven parametr jmeno i prijmeni 
        if (isset($getparametry["jmeno"]) && isset($getparametry["prijmeni"]))
        {
            $radekZDb = $this->explorer->table('narozeniny')->where([
                'jmeno'=> $getparametry["jmeno"],
                'prijmeni' => $getparametry["prijmeni"]
            ])->fetch();
            if($radekZDb)
            {
                $poleZDB = $radekZDb->toArray(); 
                $poleZDB["vek"] = $this->vypocitejVek((string)$poleZDB["datum-narozeni"]);
                $poleZDB["datum-narozeni"] = DateTime::from($poleZDB["datum-narozeni"])->format('d. m. Y');
                return $poleZDB; 
                exit();
            }else{ //databaze nenasla hledanou osobu
                return $this->dataNenalezena;
                exit();
            }
        }else{ //byly zadany nejake parametry ale ne ty spravne
            return $this->nevalidniData;
            exit();
        }
    }

    public function vratOsoby(){
        $vsechnyNarozeniny = $this->explorer->table('narozeniny')->fetchAll();
        $zformatovaneVsechnyNarozeniny = []; 
        foreach($vsechnyNarozeniny AS $jedenRadek){
            $datum = $jedenRadek->{'datum-narozeni'};
            $zformatovaneDatum = DateTime::from($datum)->format('d. m. Y');
            $zformatovaneVsechnyNarozeniny[] =
            [
                'jmeno' => $jedenRadek->jmeno,
                'prijmeni' => $jedenRadek->prijmeni,
                'datum-narozeni' => $zformatovaneDatum,
                'vek' => $this->vypocitejVek((string)$datum)
            ];
        };
        return $zformatovaneVsechnyNarozeniny;
        exit();
    }

    public function ulozOsobu(array $getparametry){
        if(isset($getparametry["jmeno"]) && isset($getparametry["prijmeni"]) && isset($getparametry["datum-narozeni"])){
            try{
                $this->explorer->table('narozeniny')->insert([
                    'jmeno' => $getparametry["jmeno"],
                    'prijmeni' => $getparametry["prijmeni"],
                    'datum-narozeni' => $getparametry["datum-narozeni"]
                ]);
                $uspesneVlozeniZaznamu=[
                    "success" => true,
                    "message" => "Osoba jménem {$getparametry["jmeno"]} {$getparametry["prijmeni"]} byla úspěšně vložena do aplikace."
                ];
                return $uspesneVlozeniZaznamu;
                exit();
            }catch(\Exception $e){
                $this->chyba["error"] = $e->getMessage();
                return $this->chyba;
                exit();
            }
        }else{
            return $this->nevalidniData;
            exit();
        }

    }

    public function odstranOsobu(array $getparametry){
        if ($getparametry){
            //jsou poslany 2 parametry jmeno a prijmeni
            if (isset($getparametry["jmeno"]) && isset($getparametry["prijmeni"])){
                    $radekZDb = $this->explorer->table('narozeniny')->where([
                        'jmeno'=> $getparametry["jmeno"],
                        'prijmeni' => $getparametry["prijmeni"]
                    ])->delete();
                    if ($radekZDb > 0){
                        $vysledek=[
                            'success' => true,
                            'message' => "Osoba {$getparametry['jmeno']} {$getparametry['prijmeni']} byla uspesne smazana."
                        ];
                        return $vysledek;
                        exit();
                    } else{
                        return $this->dataNenalezena;
                        exit();
                    }
            }else{ // nejaky parametr chybi
                    return $this->nevalidniData;
                    exit();
                    }
        }else{ // nebyl poslan zadny parametr
                    return $this->nevalidniData;
                    exit();
        }

    }

    public function upravOsobu(array $getparametry){
        if (isset($getparametry["jmeno"]) && isset($getparametry["prijmeni"]) && isset($getparametry["datum-narozeni"])){
            try{
                $radekZDb = $this->explorer->table('narozeniny')->where([
                    'jmeno'=> $getparametry["jmeno"],
                    'prijmeni' => $getparametry["prijmeni"]
                ]);
                if($radekZDb->count() > 0){
                    $radekZDb->update([
                        'datum-narozeni' => $getparametry["datum-narozeni"]
                    ]);
                    $vysledek=[
                        'success' => true,
                        'message' => "Osoba {$getparametry['jmeno']} {$getparametry['prijmeni']} byla uspesne upravena."
                    ];
                    return $vysledek;
                } else { // v databazi se nenasel odpovidajici radek
                    return $this->dataNenalezena;
                }
            }catch(\Exception $e){
                return $this->dataNenalezena;
            }
        }else{ // spatne zadane parametry dotazu
            return $this->nevalidniData;
        }
    }



    public function vratInfoProNevalidniEndpointy(){
        $vysledek=[
            'success' => false,
            'message' => "Pro pouziti REST API je potřeba odeslat http požadavek ve správném formátu, specifikaci meho REST API naleznete zde: www.marketatrnkova.cz/api-dokumentace.pdf"
        ];
        return $vysledek;
    }

    public function vratVsechnyOdpovedi (int $messageId){
        $vsechnyOdpovedi = $this->explorer->table('chat_narozeniny')
        ->where('mainMessage NOT ?', null)
        ->where('mainMessage', $messageId)
        ->order('messageId DESC')
        ->fetchAll();

        if ($vsechnyOdpovedi){
            $vysledky = [];
                foreach ($vsechnyOdpovedi as $odpoved) {
                    $vysledky[] = $odpoved->toArray();
                }
        }else{
            $vysledky = [
            "success" => false,
            "message" => "Vybraná zpráva nemá žádné odpovědi"
            ];
        }
   
        return $vysledky;
    }

    
}