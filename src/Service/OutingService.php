<?php

namespace App\Service;

use App\Entity\Outing;
use App\Enum\Status;
use App\Form\Model\OutingsFilter;
use App\Repository\OutingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

class OutingService extends AbstractController
{
  private OutingRepository $outingRepo;

  public function __construct(OutingRepository $outingRepo)
  {
    $this->outingRepo = $outingRepo;
  }

  public function getDefaultOutings(UserInterface $user): array
  {
    return $this->outingRepo->findByDefault($user);
  }

  public function getOutings(): array
  {
    return $this->outingRepo->findAll();
  }
    public function getOuting(int $id): ?Outing
    {
        return $this->outingRepo->find($id);
    }

  public function getFilteredOutings(array $outings, UserInterface $user, OutingsFilter $filter): array
  {

    return $this->outingRepo->findByFilters(
        $outings,
        $user,
        $filter->getCampusChoice(),
        $filter->getTitleSearch(),
        $filter->getStartDate(),
        $filter->getEndDate(),
        $filter->getIsHost(),
        $filter->getIsEntered(),
        $filter->getIsNotEntered(),
        $filter->getIsPast()
    );
  }

  public function updateOutingStatuses(): void
  {
    $outings = $this->outingRepo->findOutingsToUpdate();
    $now = new \DateTime();

    foreach ($outings as $outing) {
      $startAt = $outing->getStartAt();
      $endAt = $outing->getEndAt();

      if ($startAt <= $now && $now < $endAt) {
        $outing->setStatus(Status::ONGOING);
      } elseif ($endAt <= $now) {
        $outing->setStatus(Status::PAST);
      }
    }
    $this->outingRepo->getEntityManager()->flush();
  }

  public function validateOutingPermissions(Outing $outing): void {
      if ($outing->getHost() !== $this->getUser()) {
          throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette sortie.');
      }
      if ($outing->getStatus() === Status::CLOSED) {
          throw $this->createAccessDeniedException('Cette sortie a été clôturée.');
      }
      if ($outing->getStatus() === Status::ONGOING) {
          throw $this->createAccessDeniedException('Cette sortie est en cours.');
      }
      if ($outing->getStatus() === Status::PAST) {
          throw $this->createAccessDeniedException('Cette sortie est terminée.');
      }
      if ($outing->getStatus() === Status::CANCELED) {
          throw $this->createAccessDeniedException('Cette sortie a été annulée.');
      }
  }

  public function deleteOuting(Outing $outing): void {
      $this->outingRepo->getEntityManager()->remove($outing);
      $this->outingRepo->getEntityManager()->flush();
  }
}