<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ReservationRepository;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationHistoryController extends AbstractController
{
    #[Route('/librarian/reservation/history', name: 'reservation_history')]
    public function index(ReservationRepository $reservationRepository)
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservations = $reservationRepository->findBy(
            ['user' => $user],
            ['dateReservation' => 'DESC']
        );

        return $this->render('reservation_history/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
