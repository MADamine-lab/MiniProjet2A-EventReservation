<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findUpcoming();
        return $this->render('event/home.html.twig', ['events' => $events]);
    }

    #[Route('/events', name: 'app_events')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findUpcoming();
        return $this->render('event/index.html.twig', ['events' => $events]);
    }

    #[Route('/events/{id}', name: 'app_event_show', requirements: ['id' => '\d+'])]
    public function show(int $id, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }
        return $this->render('event/show.html.twig', ['event' => $event]);
    }

    #[Route('/events/{id}/reserve', name: 'app_event_reserve', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function reserve(
        int $id,
        Request $request,
        EventRepository $eventRepository,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $em
    ): Response {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getAvailableSeats() <= 0) {
            $this->addFlash('error', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('app_event_show', ['id' => $id]);
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setEvent($event);
            $em->persist($reservation);
            $em->flush();

            return $this->redirectToRoute('app_reservation_confirm', ['id' => $reservation->getId()]);
        }

        return $this->render('event/reserve.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/confirm/{id}', name: 'app_reservation_confirm', requirements: ['id' => '\d+'])]
    public function confirm(int $id, ReservationRepository $reservationRepository): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        return $this->render('event/confirm.html.twig', ['reservation' => $reservation]);
    }
}
