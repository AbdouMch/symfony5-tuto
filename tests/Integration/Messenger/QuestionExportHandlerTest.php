<?php

namespace App\Tests\Integration\Messenger;

use App\Entity\Export;
use App\Entity\ExportStatus;
use App\Exporter\Question\QuestionExportCache;
use App\Factory\QuestionFactory;
use App\Factory\UserFactory;
use App\Messenger\Message\QuestionExport;
use App\Messenger\MessageHandler\Exporter\Question\QuestionExportHandler;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class QuestionExportHandlerTest extends KernelTestCase
{
    
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    public function testHandlerMarksExportAsCompleted(): void
    {
        $completedStatus = $this->createExportStatus(ExportStatus::COMPLETED);

        $user = UserFactory::createOne()->object();
        QuestionFactory::createMany(2, ['owner' => $user]);

        $export = new Export();
        $export->setEntity('question')
            ->setUserId($user->getId())
            ->setProgress(0)
            ->setData(null);
        $this->em->persist($export);
        $this->em->flush();

        $pdf = $this->createMock(Pdf::class);
        $pdf->expects($this->once())->method('generateFromHtml');

        $cache = $this->createMock(QuestionExportCache::class);
        $cache->expects($this->once())->method('saveExportForUser');

        $twig = static::getContainer()->get(Environment::class);

        $handler = new QuestionExportHandler(
            $this->em,
            $cache,
            $twig,
            $pdf,
            sys_get_temp_dir()
        );

        $handler(new QuestionExport($export->getId()));

        $this->em->refresh($export);
        $this->assertSame(100, $export->getProgress());
        $this->assertSame(ExportStatus::COMPLETED, $export->getStatus()->getConstantCode());
    }

    public function testHandlerDoesNothingForUnknownExportId(): void
    {
        $pdf = $this->createMock(Pdf::class);
        $pdf->expects($this->never())->method('generateFromHtml');

        $cache = $this->createMock(QuestionExportCache::class);
        $twig = static::getContainer()->get(Environment::class);

        $handler = new QuestionExportHandler(
            $this->em,
            $cache,
            $twig,
            $pdf,
            sys_get_temp_dir()
        );

        // Should return early without throwing
        $handler(new QuestionExport(99999));

        $this->assertTrue(true);
    }

    private function createExportStatus(string $constantCode): ExportStatus
    {
        $status = new ExportStatus();
        $status->setName($constantCode)->setConstantCode($constantCode);
        $this->em->persist($status);
        $this->em->flush();

        return $status;
    }
}
