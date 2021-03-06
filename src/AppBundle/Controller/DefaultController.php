<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Operation;
use AppBundle\Repository\OperationRepository;
use DateInterval;
use DateTime;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @Template()
     *
     * @QueryParam(name="dateFrom", nullable=true)
     * @QueryParam(name="dateTo", nullable=true)
     * @QueryParam(name="contact", nullable=true, requirements="\d+")
     * @QueryParam(name="tag", nullable=true, requirements="\d+")
     * @QueryParam(name="group", nullable=true, default="daily", requirements="daily|weekly|monthly|yearly")
     *
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string   $group
     *
     * @return array|RedirectResponse
     */
    public function indexAction(DateTime $dateFrom = null, DateTime $dateTo = null, $group = OperationRepository::GROUP_DAILY)
    {
        /** @var OperationRepository $operationRepository */
        $operationRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Operation::class);

        $date = new DateTime();
        if (null === $dateFrom) {
            $dateFrom = new DateTime($date->format('Y-m'));
        }

        if (null === $dateTo) {
            $dateTo = clone $dateFrom;
            $dateTo->add(new DateInterval('P1M'));
        }

        $datePrevFrom = clone $dateFrom;
        $datePrevTo = clone $dateTo;
        $dateNextFrom = clone $dateFrom;
        $dateNextTo = clone $dateTo;

        $interval = date_diff($dateFrom, $dateTo);
        $dates = [
            'prevFrom' => $datePrevFrom->sub($interval),
            'prevTo' => $datePrevTo->sub($interval),
            'nextFrom' => $dateNextFrom->add($interval),
            'nextTo' => $dateNextTo->add($interval),
        ];

        $statistics = [
            'start' => $operationRepository->getOperationsSumQB($this->getUser(), null, $dateFrom)->execute()->fetchColumn(),
            'balance' => $operationRepository->getOperationsSumQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetchColumn(),
            'expenses' => $operationRepository->getOperationsSumExpensesQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetch(),
            'incomes' => $operationRepository->getOperationsSumIncomesQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetch(),
        ];

        $operations = [
            'contact' => [
                'expenses' => $operationRepository->getOperationsSumGroupByContactExpensesQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetchAll(),
                'incomes' => $operationRepository->getOperationsSumGroupByContactIncomesQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetchAll(),
            ],
            'tag' => [
                'expenses' => $operationRepository->getOperationsSumGroupByTagExpensesQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetchAll(),
                'incomes' => $operationRepository->getOperationsSumGroupByTagIncomesQB($this->getUser(), $dateFrom, $dateTo)->execute()->fetchAll(),
            ],
        ];

        $timeLine = $operationRepository->getOperationsTimeLineGroupByDateQB($this->getUser(), $dateFrom, $dateTo, $group);

        return [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dates' => $dates,
            'group' => $group,
            'statistics' => $statistics,
            'operations' => $operations,
            'timeLine' => $timeLine,
        ];
    }
}
