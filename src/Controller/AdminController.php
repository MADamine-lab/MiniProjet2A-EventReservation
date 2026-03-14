<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        EventRepository $eventRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $events = $eventRepository->findAllOrderedByDate();
        $recentReservations = $reservationRepository->findRecentReservations(5);
        $totalReservations = count($reservationRepository->findAll());
        $totalEvents = count($events);

        return $this->render('admin/dashboard.html.twig', [
            'events' => $events,
            'recentReservations' => $recentReservations,
            'totalReservations' => $totalReservations,
            'totalEvents' => $totalEvents,
        ]);
    }

    #[Route('/events', name: 'admin_events')]
    public function events(EventRepository $eventRepository): Response
    {
        return $this->render('admin/events/index.html.twig', [
            'events' => $eventRepository->findAllOrderedByDate(),
        ]);
    }

    #[Route('/events/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function newEvent(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $event->setImage($newFilename);
            }
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_events');
        }

        return $this->render('admin/events/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/events/{id}/edit', name: 'admin_event_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editEvent(
        int $id,
        Request $request,
        EventRepository $eventRepository,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                // Delete old image
                if ($event->getImage()) {
                    $oldImagePath = $this->getParameter('images_directory') . '/' . $event->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $event->setImage($newFilename);
            }
            $em->flush();
            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('admin_events');
        }

        return $this->render('admin/events/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/events/{id}/delete', name: 'admin_event_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEvent(
        int $id,
        Request $request,
        EventRepository $eventRepository,
        EntityManagerInterface $em
    ): Response {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            if ($event->getImage()) {
                $imagePath = $this->getParameter('images_directory') . '/' . $event->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_events');
    }

    #[Route('/events/{id}/reservations', name: 'admin_event_reservations', requirements: ['id' => '\d+'])]
    public function eventReservations(
        int $id,
        EventRepository $eventRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $reservations = $reservationRepository->findByEvent($id);

        return $this->render('admin/reservations/index.html.twig', [
            'event' => $event,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservations', name: 'admin_all_reservations')]
    public function allReservations(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findRecentReservations(100);
        return $this->render('admin/reservations/all.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
