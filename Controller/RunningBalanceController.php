<?php

/*
 * This file is part of the "DemoBundle" for Kimai.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RunningBalanceBundle\Controller;

use App\Configuration\LocaleService;
use App\Controller\AbstractController;
use App\Project\ProjectStatisticService;
use App\Reporting\ProjectDetails\ProjectDetailsModel;
use App\Reporting\ProjectDetails\ProjectDetailsQuery;
use App\Entity\Timesheet;
use App\Utils\PageSetup;
use App\Repository\TimesheetRepository;
use KimaiPlugin\RunningBalanceBundle\Report\RunningBalanceReportForm;
use KimaiPlugin\RunningBalanceBundle\Report\RunningBalanceReportFormQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\WorkingTime\WorkingTimeService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use \Datetime;


#[Route(path: '/admin/runningbalance')]
final class RunningBalanceController extends AbstractController
{

    #[Route(path: '/report', name: 'runningbalance_report', methods: ['GET', 'POST'])]
    public function report(Request $request, ProjectStatisticService $service, WorkingTimeService $workingTimeService, TimesheetRepository $repository): Response
    {

        $user = $this->getUser();

        $query = new RunningBalanceReportFormQuery($user);
        $form = $this->createFormForGetRequest(RunningBalanceReportForm::class, $query);
        $form->submit($request->query->all());


        $projectView = null;
        $projectDetails = null;

        $project = $query->getProject();
        $timeResolution = $query->getTimeResolution();
        $runningDurationBalance = [];
        $data = [
            'report_title' => 'Running Balance report',
            'form' => $form->createView(),
            'running_balance' => $runningDurationBalance,
            'project' => $project,
            'timeResolution' => $timeResolution,
            'days_in_month' => [],
        ];

        if($project !== null){
            $dateFactory = $this->getDateTimeFactory();
            $query = new ProjectDetailsQuery($dateFactory->createDateTime(), $user);
            $query->setProject($project);
            $model = $service->getProjectsDetails($query);
            $durations_by_day = [];

            if ($timeResolution === 1) {
                $begin = $project->getStart();
                $end = $project->getEnd() ?? new DateTime();
            $qb = $repository->createQueryBuilder('t');                
                $qb
            ->select('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('DATE(t.date) as day')
            ->andWhere('t.project = :project')
            ->andWhere($qb->expr()->between('t.date', ':begin', ':end'))
            ->setParameter('project', $query->getProject())
            ->setParameter('begin', $begin->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->addGroupBy('day');
            $results = $qb->getQuery()->getResult();
                foreach ($results as $result) {
                    $durations_by_day[$result['day']] = $result['duration'];
                }

            }

            $idx = 0;
            if ($project->getTimeBudget() > 0 && $project->isMonthlyBudget() && $project->getStart() !== null) {
                foreach($model->getYears() as $year) {
                    if($year->getYear() > $query->getToday()->format('Y')) {
                    //    break;
                    }
                    foreach($year->getMonths() as $month) {
                        if(($year->getYear() >= $query->getToday()->format('Y') && $month->getMonthNumber() > $query->getToday()->format('m'))) {
                    //      break;
                        }
                        if($timeResolution === 0) {
                            $previous = $runningDurationBalance[$idx - 1] ?? 0;
                            $runningDurationBalance[] = $previous - $project->getTimeBudget() + $month->getBillableDuration();
                            //$month->setBillableDurationBalance($runningDurationBalance[$idx]);
                            $idx++;
                        } else {
                            $daysinmonth = cal_days_in_month(CAL_GREGORIAN, $month->getMonthNumber(), $year->getYear());
                            $data["days_in_month"][] = $daysinmonth;
                            for($i = 1; $i <= $daysinmonth; $i++) {
                                if($project->getStart() > new DateTime($year->getYear() . '-' . $month->getMonthNumber() . '-' . $i)) {
                                    $runningDurationBalance[] = 0;
                                    continue;
                                }
                                $previous = $runningDurationBalance[$idx - 1] ?? 0;
                                $today = $durations_by_day[$year->getYear() . '-' . $month->getMonthNumber() . '-' . $i] ?? 0;
                                $runningDurationBalance[$idx] = $previous - $project->getTimeBudget() / $daysinmonth + $today;
                                $idx++;    
                            }
                        }
                    }
                }
            }
            $data["project_details"] = $model;
            $data["running_duration_balance"] = $runningDurationBalance;

        } 



        return $this->render('@RunningBalance/report.html.twig', $data);
    }
}
