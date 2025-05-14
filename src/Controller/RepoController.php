<?php

namespace App\Controller;

use App\Entity\Repo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class RepoController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, SecurityBundleSecurity $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/repos', name: 'get_repos', methods: ['GET'])]
    public function getRepos(): JsonResponse
    {
        $user = $this->security->getUser();
        $repos = $this->entityManager->getRepository(Repo::class)->findBy(['owner' => $user]);
        
        $reposData = [];
        foreach ($repos as $repo) {
            $client = $repo->getClient();
            $reposData[] = [
                'id' => $repo->getId(),
                'projectname' => $repo->getProjectname(),
                'description' => $repo->getDescription(),
                'fechaInicio' => $repo->getFechaInicio()->format('Y-m-d'),
                'fechaFin' => $repo->getFechaFin() ? $repo->getFechaFin()->format('Y-m-d') : null,
                'fileName' => $repo->getFileName(),
                'client' => $client ? [
                    'id' => $client->getId(),
                    'name' => $client->getName()
                ] : null,
            ];
        }
        
        return new JsonResponse($reposData);
    }

    #[Route('/newrepo', name: 'create_repo', methods: ['POST'])]
    public function createRepo(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['projectname']) || !isset($data['fechaInicio'])) {
            return new JsonResponse(['error' => 'Faltan datos obligatorios'], Response::HTTP_BAD_REQUEST);
        }
        
        $repo = new Repo();
        $repo->setProjectname($data['projectname']);
        $repo->setDescription($data['description'] ?? null);
        $repo->setFechaInicio(new \DateTime($data['fechaInicio']));
        
        if (isset($data['fechaFin'])) {
            $repo->setFechaFin(new \DateTime($data['fechaFin']));
        }
        
        if (isset($data['file'])) {
            $repo->setFile($data['file']);
        }
        
        if (isset($data['fileName'])) {
            $repo->setFileName($data['fileName']);
        } else {
            $repo->setFileName($data['projectname']);
        }
        
        if (isset($data['client'])) {
            $repo->setClient($data['client']);
        }
        
        // Establecer el propietario como el usuario actual
        $user = $this->security->getUser();
        $repo->setOwner($user);
        
        $this->entityManager->persist($repo);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'id' => $repo->getId(),
            'message' => 'Repositorio creado con éxito'
        ], Response::HTTP_CREATED);
    }

    #[Route('/updaterepo/{id}', name: 'update_repo', methods: ['PUT'])]
    public function updateRepo(Request $request, int $id): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($id);
        
        if (!$repo) {
            return new JsonResponse(['error' => 'Repositorio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario actual es el propietario
        $user = $this->security->getUser();
        if ($repo->getOwner() !== $user) {
            return new JsonResponse(['error' => 'No tienes permiso para modificar este repositorio'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['projectname'])) {
            $repo->setProjectname($data['projectname']);
        }
        
        if (isset($data['description'])) {
            $repo->setDescription($data['description']);
        }
        
        if (isset($data['fechaInicio'])) {
            $repo->setFechaInicio(new \DateTime($data['fechaInicio']));
        }
        
        if (isset($data['fechaFin'])) {
            $repo->setFechaFin(new \DateTime($data['fechaFin']));
        }
        
        if (isset($data['file'])) {
            $repo->setFile($data['file']);
        }
        
        if (isset($data['fileName'])) {
            $repo->setFileName($data['fileName']);
        }
        
        if (isset($data['client'])) {
            $repo->setClient($data['client']);
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Repositorio actualizado con éxito']);
    }

    #[Route('/deleterepo/{id}', name: 'delete_repo', methods: ['DELETE'])]
    public function deleteRepo(int $id): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($id);
        
        if (!$repo) {
            return new JsonResponse(['error' => 'Repositorio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario actual es el propietario
        $user = $this->security->getUser();
        if ($repo->getOwner() !== $user) {
            return new JsonResponse(['error' => 'No tienes permiso para eliminar este repositorio'], Response::HTTP_FORBIDDEN);
        }
        
        $this->entityManager->remove($repo);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Repositorio eliminado con éxito']);
    }

    #[Route('/repos/{id}/colaboradores', name: 'add_colaborador', methods: ['POST'])]
    public function addColaborador(Request $request, int $id): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($id);
        
        if (!$repo) {
            return new JsonResponse(['error' => 'Repositorio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario actual es el propietario
        $user = $this->security->getUser();
        if ($repo->getOwner() !== $user) {
            return new JsonResponse(['error' => 'No tienes permiso para añadir colaboradores a este repositorio'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['userId'])) {
            return new JsonResponse(['error' => 'Falta el ID del usuario colaborador'], Response::HTTP_BAD_REQUEST);
        }
        
        $colaborador = $this->entityManager->getRepository(User::class)->find($data['userId']);
        
        if (!$colaborador) {
            return new JsonResponse(['error' => 'Usuario colaborador no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el colaborador no sea el propietario
        if ($colaborador === $repo->getOwner()) {
            return new JsonResponse(['error' => 'El propietario no puede ser añadido como colaborador'], Response::HTTP_BAD_REQUEST);
        }
        
        // Verificar que el colaborador no esté ya añadido
        if ($repo->getColaboradores()->contains($colaborador)) {
            return new JsonResponse(['error' => 'El usuario ya es colaborador de este repositorio'], Response::HTTP_BAD_REQUEST);
        }
        
        $repo->addColaborador($colaborador);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Colaborador añadido con éxito']);
    }

    #[Route('/repos/{id}/colaboradores/{userId}', name: 'remove_colaborador', methods: ['DELETE'])]
    public function removeColaborador(int $id, int $userId): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($id);
        
        if (!$repo) {
            return new JsonResponse(['error' => 'Repositorio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario actual es el propietario
        $user = $this->security->getUser();
        if ($repo->getOwner() !== $user) {
            return new JsonResponse(['error' => 'No tienes permiso para eliminar colaboradores de este repositorio'], Response::HTTP_FORBIDDEN);
        }
        
        $colaborador = $this->entityManager->getRepository(User::class)->find($userId);
        
        if (!$colaborador) {
            return new JsonResponse(['error' => 'Usuario colaborador no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        if (!$repo->getColaboradores()->contains($colaborador)) {
            return new JsonResponse(['error' => 'El usuario no es colaborador de este repositorio'], Response::HTTP_BAD_REQUEST);
        }
        
        $repo->removeColaborador($colaborador);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Colaborador eliminado con éxito']);
    }

    #[Route('/repo/{id}', name: 'get_repo', methods: ['GET'])]
    public function getRepo(int $id): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($id);
        
        if (!$repo) {
            return new JsonResponse(['error' => 'Repositorio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario actual es el propietario o colaborador
        $user = $this->security->getUser();
        if ($repo->getOwner() !== $user && !$repo->getColaboradores()->contains($user)) {
            return new JsonResponse(['error' => 'No tienes permiso para ver este repositorio'], Response::HTTP_FORBIDDEN);
        }
        
        $client = $repo->getClient();
        
        return new JsonResponse([
            'id' => $repo->getId(),
            'projectname' => $repo->getProjectname(),
            'description' => $repo->getDescription(),
            'fechaInicio' => $repo->getFechaInicio()->format('Y-m-d'),
            'fechaFin' => $repo->getFechaFin() ? $repo->getFechaFin()->format('Y-m-d') : null,
            'fileName' => $repo->getFileName(),
            'client' => $client ? [
                'id' => $client->getId(),
                'name' => $client->getName() 
            ] : null,
            'owner' => [
                'id' => $repo->getOwner()->getId(),
                'username' => $repo->getOwner()->getUsername()
            ],
            'colaboradores' => array_map(function($colaborador) {
                return [
                    'id' => $colaborador->getId(),
                    'username' => $colaborador->getUsername()
                ];
            }, $repo->getColaboradores()->toArray())
        ]);
    }

    #[Route('/repos/{id}/colaboradores', name: 'get_colaboradores', methods: ['GET'])]
    public function getColaboradores(int $id): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($id);
        
        if (!$repo) {
            return new JsonResponse(['error' => 'Repositorio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario actual es el propietario o colaborador
        $user = $this->security->getUser();
        if ($repo->getOwner() !== $user && !$repo->getColaboradores()->contains($user)) {
            return new JsonResponse(['error' => 'No tienes permiso para ver los colaboradores de este repositorio'], Response::HTTP_FORBIDDEN);
        }
        
        $colaboradores = [];
        foreach ($repo->getColaboradores() as $colaborador) {
            $colaboradores[] = [
                'id' => $colaborador->getId(),
                'username' => $colaborador->getUsername(),
                'email' => $colaborador->getEmail()
            ];
        }
        
        return new JsonResponse($colaboradores);
    }

    #[Route('/repos/colaboraciones', name: 'get_colaboraciones', methods: ['GET'])]
    public function getColaboraciones(): JsonResponse
    {
        $user = $this->security->getUser();
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('r')
           ->from(Repo::class, 'r')
           ->join('r.colaboradores', 'c')
           ->where('c = :user')
           ->setParameter('user', $user);
        
        $repos = $qb->getQuery()->getResult();
        
        $reposData = [];
        foreach ($repos as $repo) {
            $client = $repo->getClient();
            $reposData[] = [
                'id' => $repo->getId(),
                'projectname' => $repo->getProjectname(),
                'description' => $repo->getDescription(),
                'fechaInicio' => $repo->getFechaInicio()->format('Y-m-d'),
                'fechaFin' => $repo->getFechaFin() ? $repo->getFechaFin()->format('Y-m-d') : null,
                'fileName' => $repo->getFileName(),
                'client' => $client ? [
                    'id' => $client->getId(),
                    'name' => $client->getName()
                ] : null,
                'owner' => [
                    'id' => $repo->getOwner()->getId(),
                    'username' => $repo->getOwner()->getUsername()
                ]
            ];
        }
        
        return new JsonResponse($reposData);
    }
}
