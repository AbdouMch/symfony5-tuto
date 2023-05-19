<?php

namespace App\Messenger\MessageHandler\Exporter\Question;

use App\Entity\Export;
use App\Entity\ExportStatus;
use App\Entity\Question;
use App\Entity\User;
use App\Exporter\Question\QuestionExportCache;
use App\Messenger\Message\QuestionExport;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Environment;

class QuestionExportHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private QuestionExportCache $cache;
    private Pdf $pdfGenerator;
    private Environment $twig;
    private string $exportDir;

    public function __construct(
        EntityManagerInterface $em,
        QuestionExportCache $cache,
        Environment $twig,
        Pdf $pdfGenerator,
        string $exportDir
    )
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->pdfGenerator = $pdfGenerator;
        $this->twig = $twig;
        $this->exportDir = $exportDir;
    }

    public function __invoke(QuestionExport $message): void
    {
        /** @var Export|null $export */
        $export = $this->em->getRepository(Export::class)->find($message->getExportId());
        if (null === $export) {
            return;
        }
        // TODO add pdf export
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->find($export->getUserId());
        $questions = $this->em->getRepository(Question::class)
            ->findBy([
                'owner' => $user,
            ],
            ['askedAt' => 'DESC'],
            );
        $html = $this->twig->render('export/question/question.html.twig', [
           'export_title' => 'Question Export',
           'questions' => $questions,
        ]);
        $filename = $this->getExportFileName($user);
        $this->pdfGenerator->generateFromHtml($html, $filename);

        $completedStatus = $this->em->getRepository(ExportStatus::class)->findOneByConstantCode(ExportStatus::COMPLETED);

        $export->setStatus($completedStatus)
            ->setResult($filename)
            ->setProgress(100);

        $this->em->flush();

        $this->cache->saveExportForUser($user, $export);
    }

    private function getExportFileName(User $user): string
    {
        $today = (new \DateTime())->format("YmdHis");

        return sprintf("%s/questions/questions_%s_%s.pdf", $this->exportDir, $today, $user->getId());
    }
}
