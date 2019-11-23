<?php declare(strict_types=1);

namespace App\Controller;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    private $baseUrl = "https://ckan.dataplatform.nl/api/3/action/datastore_search_sql?sql=";
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request)
    {
        if ($request->getMethod() === Request::METHOD_POST) {
            return $this->redirect('/sniffer/' . $request->get('sniffer') . '/' . $request->get('from') . '/' . $request->get('to'));
        }

        return $this->render('index/index.html.twig', []);
    }

    /**
     * @Route("/sniffer/{sniffer}/{from}/{to}", name="sniffer")
     */
    public function sniffer(string $sniffer, string $from, string $to)
    {
        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $data = $this->getData($sniffer, $fromDate, $toDate);
        $polyline = [];
        foreach ($data['result']['records'] as $record) {
            $polyline[] = [$record['lat'], $record['lon']];
        }

        return $this->render('index/sniffer.html.twig', [
            'sniffer' => $sniffer,
            'data' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'polyline' => json_encode($polyline),
        ]);
    }

    private function getData(string $sniffer, Carbon $fromDate, Carbon $toDate): array
    {
        $cache = new FilesystemAdapter();

        return $cache->get($sniffer . $fromDate->timestamp . $toDate->timestamp, function () use ($sniffer, $fromDate, $toDate) {
            $client = HttpClient::create();
            $response = $client->request('GET', $this->createUrl($sniffer, $fromDate, $toDate));

            return $response->toArray();
        });
    }

    private function createUrl(string $sniffer, Carbon $fromDate, Carbon $toDate): string
    {
        return $this->baseUrl . "SELECT * FROM%20snuffelfiets_gdpr(" . $fromDate->timestamp . ", " . $toDate->timestamp . ") where entity = '" . $sniffer . "'";
    }
}