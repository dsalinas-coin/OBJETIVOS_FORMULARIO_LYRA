<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Pagos;

class DefaultController extends AbstractController
{
    private $httpClient;
    private $user = "29014176";
    private $pass = "testpassword_gRaX7dCdNSnLmSmEHD4XM0EMGaPQTxfKWUJ6ECdnb6fpa";

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/', name: 'homepage')]
    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    #[Route('/incrustadopaso1', name: 'incrustado')]
    public function incrustadopaso1Action()
    {
        return $this->render('default/paso1.html.twig', [
            'nextcontroller' => 'incrustado2'
        ]);
    }

    #[Route('/incrustadopaso2/{arepa}/{precio}/{orderId}', name: 'incrustado2')]
    public function incrustadopaso2Action($arepa, $precio, $orderId, EntityManagerInterface $entityManager)
    { 
        $public_key = base64_encode($this->user . ":" . $this->pass);
        $payload = array(
            "amount" =>   $precio,
            "currency" => "ARS",
            "orderId" => $orderId,
	        "customer" => array(
                "email" => "dsalinas@cobroinmediato.tech")
        );
        $response = $this->httpClient->request(
            'POST',
            'https://api.cobroinmediato.tech/api-payment/V4/Charge/CreatePayment', [
            'headers' => [
                'Content-Type' => 'application/json',
                "Authorization" => "Basic " . $public_key
            ],
            "body" => $payload
        ]);

        $content = $response->getContent();
        $data = json_decode($content);
        $token = $data->answer->formToken;
        
        if($data->status == "SUCCESS"){

            $pagos = new Pagos();
            $pagos->setOrderId($orderId);
            $pagos->setArepa($arepa);
            $pagos->setPrecio($precio);
    
            $entityManager->persist($pagos);
            $entityManager->flush();
    
            //return new Response('Se ha creado el pago con id: '.$pagos->getId());
            //dd($orderId);

            return $this->render('default/incrustado.html.twig', [
                'arepa' => $arepa,
                'precio' => $precio,
                'orderId' => $orderId,
                'token' => $token
            ]);
        };

    }
    
    #[Route('/exito', name: 'success')]
    public function successAction()
    {
        return $this->render('default/exito.html.twig');
    }

    #[Route('/refused', name: 'refused')]
    public function rechazoAction()
    {
        return $this->render('default/refused.html.twig');
    }
}