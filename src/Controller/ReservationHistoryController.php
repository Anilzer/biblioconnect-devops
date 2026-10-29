<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationHistoryController extends AbstractController
{
    #[Route(
        '/librarian/reservation/history',
        name: 'reservation_history'
    )]
    public function index(
        ReservationRepository $reservationRepository
    ): Response {
        // Cette page est réservée au bibliothécaire/admin.
        $this->denyAccessUnlessGranted('ROLE_LIBRARIAN');

        // Afficher toutes les réservations des clients.
        $reservations = $reservationRepository->findBy(
            [],
            ['dateReservation' => 'DESC']
        );

        return $this->render('reservation_history/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
