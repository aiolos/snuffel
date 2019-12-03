<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            return $this->redirect('/sniffer/' . $request->get('sniffer'));
        }

        $sniffers = $this->getRepository()->findAllSniffers();

        return $this->render('index/index.html.twig', [
            'sniffers' => $sniffers,
        ]);
    }

    /**
     * @Route("/sniffer/{sniffer}", name="show")
     */
    public function show(Request $request, string $sniffer)
    {

        return $this->render('index/show.html.twig', [
            'sniffer' => $sniffer,
            'dates' => $this->getRepository()->findAllDatesForSniffer($sniffer),
            'today' => Carbon::today(),
        ]);
    }

    /**
     * @Route("/sniffer/{sniffer}/show/{from}/{to}/{particles}", name="showMeasurements")
     */
    public function showMap(string $sniffer, string $from, string $to, string $particles = 'pm25')
    {
        $fromDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        $measurements = $this->getRepository()->findBySnifferInRange($sniffer, $fromDate->timestamp, $toDate->timestamp);
        $polylines = [];
        /** @var Measurement $record */
        foreach ($measurements as $measurement) {
            if (!array_key_exists($measurement->getTrip(), $polylines)) {
                $polylines[$measurement->getTrip()] = [];
            }
            $polylines[$measurement->getTrip()][] = [
                'lat' => $measurement->getLatitude(),
                'lng' => $measurement->getLongitude(),
                'particles' => ($particles === 'pm10' ? $measurement->getPm10() : $measurement->getPm25())
            ];
        }

        return $this->render('index/map.html.twig', [
            'sniffer' => $sniffer,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'dayEarlier' => $fromDate->copy()->subDay(),
            'dayLater' => $toDate->copy()->addDay(),
            'polylines' => $polylines,
            'particles' => $particles,
        ]);
    }

    /**
     * @Route("/sniffer/{sniffer}/measurements/{date}", name="measurements")
     */
    public function measurements(Request $request, string $sniffer, string $date)
    {
        $fromDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $toDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        $measurements = $this->getRepository()->findBySnifferInRange($sniffer, $fromDate->timestamp, $toDate->timestamp);

        return $this->render('index/measurements.html.twig', [
            'sniffer' => $sniffer,
            'date' => $date,
            'measurements' => $measurements,
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
            'date' => $fromDate,
            'sniffer' => $sniffer,
            'tomorrow' => $fromDate->copy()->addDay(),
            'yesterday' => $fromDate->copy()->subDay(),
        ]);
    }

    private function getData(string $sniffer, Carbon $fromDate, Carbon $toDate): array
    {
        $client = HttpClient::create();
        $response = $client->request('GET', $this->createUrl($sniffer, $fromDate, $toDate));

        return $response->toArray();
    }

    private function createUrl(string $sniffer, Carbon $fromDate, Carbon $toDate): string
    {
        return $this->baseUrl . "SELECT * FROM%20snuffelfiets_gdpr(" . $fromDate->timestamp . ", " . $toDate->timestamp . ") where entity = '" . $sniffer . "'";
    }

    private function getRepository(): MeasurementRepository
    {
        return $this->getDoctrine()->getRepository(Measurement::class);
    }
}