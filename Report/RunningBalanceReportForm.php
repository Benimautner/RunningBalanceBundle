<?php

/*
 * This file is part of the "DemoBundle" for Kimai.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RunningBalanceBundle\Report;

use App\Form\Type\ProjectType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\ReportSumType;

/**
 * @extends AbstractType<DemoReportQuery>
 */
final class RunningBalanceReportForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $projectOptions = [
            'ignore_date' => true,
            'required' => false,
            'width' => false,
            'join_customer' => true,
        ];
        $builder->add('project', ProjectType::class, $projectOptions);
        $builder->add('timeResolution', ChoiceType::class, [
            'label' => 'timeResolution',
            'choices' => [
                'monthly' => 0,
                'daily' => 1,
            ],
        ]);
/*
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($projectOptions) {
                $data = $event->getData();
                if (isset($data['project']) && !empty($data['project'])) {
                    $projectId = $data['project'];
                    $projects = [];
                    if (\is_int($projectId) || \is_string($projectId)) {
                        $projects = [$projectId];
                    }

                    $event->getForm()->add('project', ProjectType::class, array_merge($projectOptions, [
                        'projects' => $projects
                    ]));
                }
            }
        );*/
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RunningBalanceReportFormQuery::class,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}