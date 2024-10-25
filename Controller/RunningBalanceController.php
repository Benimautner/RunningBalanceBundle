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
use KimaiPlugin\RunningBalanceBundle\Report\RunningBalanceReportForm;
use KimaiPlugin\RunningBalanceBundle\Report\RunningBalanceReportFormQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/runningbalance')]
final class RunningBalanceController extends AbstractController
{

    #[Route(path: '/report', name: 'runningbalance_report', methods: ['GET', 'POST'])]
    public function report(Request $request, ProjectStatisticService $service): Response
    {

        $user = $this->getUser();

        $query = new RunningBalanceReportFormQuery($user);
        $form = $this->createFormForGetRequest(RunningBalanceReportForm::class, $query);
        $form->submit($request->query->all());


        $projectView = null;
        $projectDetails = null;

        $project = $query->getProject();
        $runningDurationBalance = [];
        $data = [
            'report_title' => 'Running Balance report',
            'form' => $form->createView(),
            'running_balance' => $runningDurationBalance,
            'project' => $project,
        ];

        if($project !== null){
            $dateFactory = $this->getDateTimeFactory();
            $query = new ProjectDetailsQuery($dateFactory->createDateTime(), $user);
            $query->setProject($project);
            $model = $service->getProjectsDetails($query);


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
                        $previous = $runningDurationBalance[$idx - 1] ?? 0;
                        $runningDurationBalance[] = $previous - $project->getTimeBudget() + $month->getBillableDuration();
                        //$month->setBillableDurationBalance($runningDurationBalance[$idx]);
                        $idx++;
                    }
                }
            }
            $data["project_details"] = $model;
            $data["running_duration_balance"] = $runningDurationBalance;
        } 



        return $this->render('@RunningBalance/report.html.twig', $data);
    }
}
