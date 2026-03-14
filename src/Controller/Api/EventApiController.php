<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/events', name: 'api_events_')]
class EventApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findUpcoming();
        $data = array_map(fn(Event $e) => $this->serializeEvent($e), $events);
        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, EventRepository $eventRepository): JsonResponse
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé.'], 404);
        }
        return $this->json(['success' => true, 'data' => $this->serializeEvent($event)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $event = new Event();
        $event->setTitle($data['title'] ?? '');
        $event->setDescription($data['description'] ?? '');
        $event->setDate(new \DateTime($data['date'] ?? 'now'));
        $event->setLocation($data['location'] ?? '');
        $event->setSeats((int)($data['seats'] ?? 0));

        $errors = $validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['success' => false, 'errors' => $errorMessages], 422);
        }

        $em->persist($event);
        $em->flush();

        return $this->json(['success' => true, 'data' => $this->serializeEvent($event)], 201);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request, EventRepository $eventRepository, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $eventRepository->find($id);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) $event->setTitle($data['title']);
        if (isset($data['description'])) $event->setDescription($data['description']);
        if (isset($data['date'])) $event->setDate(new \DateTime($data['date']));
        if (isset($data['location'])) $event->setLocation($data['location']);
        if (isset($data['seats'])) $event->setSeats((int)$data['seats']);

        $em->flush();

        return $this->json(['success' => true, 'data' => $this->serializeEvent($event)]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, EventRepository $eventRepository, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $eventRepository->find($id);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé.'], 404);
        }

        $em->remove($event);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Événement supprimé.']);
    }

    private function serializeEvent(Event $event): array
    {
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'date' => $event->getDate()?->format('Y-m-d H:i:s'),
            'location' => $event->getLocation(),
            'seats' => $event->getSeats(),
            'availableSeats' => $event->getAvailableSeats(),
            'image' => $event->getImage(),
            'createdAt' => $event->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
