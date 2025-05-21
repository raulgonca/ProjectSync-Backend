<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
final class ClientController extends AbstractController
{
    private $entityManager;
    private $clientRepository;

    public function __construct(EntityManagerInterface $entityManager, ClientRepository $clientRepository)
    {
        $this->entityManager = $entityManager;
        $this->clientRepository = $clientRepository;
    }

    #[Route('/clients', name: 'get_clients', methods: ['GET'])]
    public function getClients(): JsonResponse
    {
        $clients = $this->clientRepository->findAll();
        
        $clientsData = [];
        foreach ($clients as $client) {
            $clientsData[] = [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'cif' => $client->getCif(),
                'email' => $client->getEmail(),
                'phone' => $client->getPhone(),
                'web' => $client->getWeb()
            ];
        }
        
        return new JsonResponse($clientsData);
    }

    #[Route('/clients/{id}', name: 'get_client', methods: ['GET'])]
    public function getClient(int $id): JsonResponse
    {
        $client = $this->clientRepository->find($id);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Cliente no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return new JsonResponse([
            'id' => $client->getId(),
            'name' => $client->getName(),
            'cif' => $client->getCif(),
            'email' => $client->getEmail(),
            'phone' => $client->getPhone(),
            'web' => $client->getWeb()
        ]);
    }

    #[Route('/createclient', name: 'create_client', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createClient(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['cif'])) {
            return new JsonResponse(['error' => 'Faltan datos obligatorios'], Response::HTTP_BAD_REQUEST);
        }
        
        $client = new Client();
        $client->setName($data['name']);
        $client->setCif($data['cif']);
        
        if (isset($data['email'])) {
            $client->setEmail($data['email']);
        }
        
        if (isset($data['phone'])) {
            $client->setPhone($data['phone']);
        }
        
        if (isset($data['web'])) {
            $client->setWeb($data['web']);
        }
        
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'id' => $client->getId(),
            'message' => 'Cliente creado con éxito'
        ], Response::HTTP_CREATED);
    }

    #[Route('/updateclient/{id}', name: 'update_client', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateClient(Request $request, int $id): JsonResponse
    {
        $client = $this->clientRepository->find($id);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Cliente no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $client->setName($data['name']);
        }
        
        if (isset($data['cif'])) {
            $client->setCif($data['cif']);
        }
        
        if (isset($data['email'])) {
            $client->setEmail($data['email']);
        }
        
        if (isset($data['phone'])) {
            $client->setPhone($data['phone']);
        }
        
        if (isset($data['web'])) {
            $client->setWeb($data['web']);
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Cliente actualizado con éxito']);
    }

    #[Route('/deleteclient/{id}', name: 'delete_client', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteClient(int $id): JsonResponse
    {
        $client = $this->clientRepository->find($id);
        
        if (!$client) {
            return new JsonResponse(['error' => 'Cliente no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($client);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Cliente eliminado con éxito']);
    }
}
