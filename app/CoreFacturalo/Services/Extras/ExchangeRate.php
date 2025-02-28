<?php

namespace App\CoreFacturalo\Services\Extras;

use Carbon\Carbon;
use GuzzleHttp\Client;
use DiDom\Document as DiDom;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Exception;

class ExchangeRate
{
    protected $client;

    public function __construct()
    {

    }

    private function search($month, $year)
    {
        // $client = new  Client(['base_uri' => 'http://www.sunat.gob.pe/cl-at-ittipcam/']);
        // try {
        //     $response = $client->request('GET', "tcS01Alias?mes={$month}&anho={$year}", ['http_errors' => true, 'timeout' => 4]);
        // } catch (ClientException $e) {
        //     return $e->getResponse();
        // } catch (RequestException $e) {
        //     return $e->getResponse();
        // }

        //dd($response->getStatusCode());
        try {

            $url = "http://www.sunat.gob.pe/cl-at-ittipcam/tcS01Alias?me1s={$month}&anho={$year}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec ($ch);
            curl_close ($ch);
            // dd($response);

            //dd($response->getStatusCode());
            // if ($response->getStatusCode() == 200 && $response != "") {
            if ($response != "") {
                // $html = $response->getBody()->getContents();
                $html = $response;
                $xp = new DiDom($html);
                $sub_headings = $xp->find('form table');
                $trs = $sub_headings[1]->find('tr');
                $values = [];

                for($i = 1; $i < count($trs); $i++)
                {
                    $tr = $trs[$i];
                    $tds = $tr->find('td');

                    foreach($tds as $td)
                    {
                        $values[] = trim(preg_replace("/[\t|\n|\r]+/", '', $td->text()));
                    }
                }

                return collect($values)->chunk(3)->toArray();
            }

        } catch (Exception $e) {

            Log::info("Error consulta T/C: ".$e->getMessage());
            return false;

        }

        return false;
    }

    public function searchDate($date)
    {
        // $date = Carbon::parse($date);
        // do {
        //     $res = $this->searchByDay($date);
        //     $date = $date->addDay(-1);
        // } while (!$res);

        // return $res;

        $date = Carbon::parse($date);

        $res = $this->searchByDay($date);
        $date = $date->addDay(-1);

        if(!$res){
            $res = $this->searchByDay($date);
            $date = $date->addDay(-1);
        }

        if(!$res){
            $res = $this->searchByDay($date);
            $date = $date->addDay(-1);
        }

        if(!$res){
            $res = $this->searchByDay($date);
            $date = $date->addDay(-1);
        }

        return $res;
    }

    private function searchByDay($date)
    {
        $day = $date->day;
        $year = $date->year;
        $month = $date->month;
        $exchange_rate = new  ExchangeRate();
        $exchange_rates = $exchange_rate->search($month, $year);
        if($exchange_rates) {
            foreach ($exchange_rates as $row)
            {
                $new_row = array_values($row);
                if ($new_row[0] == (int)$day) {
                    return [
                        'date_data' => $date->format('Y-m-d'),
                        'data' => [
                            'purchase' => $new_row[1],
                            'sale' => $new_row[2]
                        ]
                    ];
                }
            }
        }

        return false;
    }
}
