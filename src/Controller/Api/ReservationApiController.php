<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/reservations', name: 'api_reservations_')]
class ReservationApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(ReservationRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $reservations = $repo->findRecentReservations(100);
        return $this->json(['success' => true, 'data' => array_map([$this, 'serialize'], $reservations)]);
    }

    #[Route('/event/{eventId}', name: 'by_event', requirements: ['eventId' => '\d+'], methods: ['GET'])]
    public function byEvent(int $eventId, ReservationRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $reservations = $repo->findByEvent($eventId);
        return $this->json(['success' => true, 'data' => array_map([$this, 'serialize'], $reservations)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EventRepository $eventRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $event = $eventRepo->find($data['eventId'] ?? 0);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé.'], 404);
        }

        if ($event->getAvailableSeats() <= 0) {
            return $this->json(['success' => false, 'message' => 'Cet événement est complet.'], 409);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setName($data['name'] ?? '');
        $reservation->setEmail($data['email'] ?? '');
        $reservation->setPhone($data['phone'] ?? '');

        $errors = $validator->validate($reservation);
        if (count($errors) > 0) {
            $msgs = [];
            foreach ($errors as $e) { $msgs[] = $e->getMessage(); }
            return $this->json(['success' => false, 'errors' => $msgs], 422);
        }

        $em->persist($reservation);
        $em->flush();

        return $this->json(['success' => true, 'data' => $this->serialize($reservation)], 201);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, ReservationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $reservation = $repo->find($id);
        if (!$reservation) {
            return $this->json(['success' => false, 'message' => 'Réservation non trouvée.'], 404);
        }
        $em->remove($reservation);
        $em->flush();
        return $this->json(['success' => true, 'message' => 'Réservation supprimée.']);
    }

    private function serialize(Reservation $r): array
    {
        return [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'email' => $r->getEmail(),
            'phone' => $r->getPhone(),
            'createdAt' => $r->getCreatedAt()?->format('Y-m-d H:i:s'),
            'event' => [
                'id' => $r->getEvent()?->getId(),
                'title' => $r->getEvent()?->getTitle(),
            ],
        ];
    }
}
