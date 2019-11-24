<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use Carbon\Carbon;
use Doctrine\Common\Persistence\ObjectRepository;
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
            return $this->redirect('/sniffer/' . $request->get('sniffer') . '/snif/' . $request->get('date'));
        }

        return $this->render('index/index.html.twig', []);
    }

    /**
     * @Route("/sniffer/{sniffer}/show/{from}/{to}", name="sniffer")
     */
    public function show(string $sniffer, string $from, string $to)
    {
        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $results = $this->getDoctrine()->getRepository(Measurement::class)->findBySnifferInRange($sniffer, $fromDate->timestamp, $toDate->timestamp);
        $polyline = [];
        /** @var Measurement $record */
        foreach ($results as $record) {
            $polyline[] = [$record->getLatitude(), $record->getLongitude()];
        }

        return $this->render('index/sniffer.html.twig', [
            'sniffer' => $sniffer,
            'results' => $results,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'polyline' => json_encode($polyline),
        ]);
    }

    /**
     * @Route("/sniffer/{sniffer}/snif/{date}", name="snif")
     */
    public function snif(string $sniffer, string $date)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $fromDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        $data = $this->getData($sniffer, $fromDate, $toDate);
        $measurements = 0;
        $foundMeasurements = 0;
        foreach ($data['result']['records'] as $record) {
            $existingMeasurement = $this->getRepository()->findBy(['sniffer' => $sniffer, 'time' => $record['time']]);
            if (count($existingMeasurement)) {
                $foundMeasurements++;
                continue;
            }
            $measurement = new Measurement();
            $measurement->setLatitude((float) $record['lat']);
            $measurement->setLongitude((float) $record['lon']);
            $measurement->setN((int) $record['n']);
            $measurement->setP((int) $record['p']);
            $measurement->setPm10((int) $record['pm10']);
            $measurement->setPm25((int) $record['pm2_5']);
            $measurement->setPoint((int) $record['point']);
            $measurement->setRh((int) $record['rh']);
            $measurement->setT((float) $record['t']);
            $measurement->setTrip((int) $record['trip']);
            $measurement->setTime((int) $record['time']);
            $measurement->setSniffer((string) $record['entity']);

            $entityManager->persist($measurement);
            $measurements++;
        }
        $entityManager->flush();

        return $this->render('index/snif.html.twig', [
            'measurements' => $measurements,
            'foundMeasurements' => $foundMeasurements,
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

    private function getRepository(): ObjectRepository
    {
        return $this->getDoctrine()->getRepository(Measurement::class);
    }
}