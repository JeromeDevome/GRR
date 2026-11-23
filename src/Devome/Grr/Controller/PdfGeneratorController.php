<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TCPDF;
use Twig\Environment;

final class PdfGeneratorController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'pdfgenerator') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        if (!grr_resumeSession() && (Settings::get('authentification_obli') == 1 || isset($_SESSION['login']))) {
            $returnUrl = rawurlencode($request->getRequestUri());

            return $this->redirect('deconnexion?auto=1&url='.$returnUrl, $bootstrap);
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            $id = $request->request->getInt('id');
            if ($id < 1) {
                throw new BadRequestHttpException('The "id" field must be a positive integer.');
            }

            $formData = $request->request->all();
            $pdfData = [];
            foreach ([
                'orga', 'nom', 'adresse', 'adresse2', 'ville', 'cp', 'cle', 'period',
                'etat', 'date', 'salle', 'jour', 'heure', 'finPeriode', 'jourPeriode',
                'heure2', 'jour2',
            ] as $key) {
                $value = $formData[$key] ?? '';
                if (!is_scalar($value)) {
                    throw new BadRequestHttpException(sprintf('The "%s" field must be a scalar value.', $key));
                }
                $pdfData[$key] = (string) $value;
            }

            $imageDirectory = $context['gcDossierImg'] ?? '';
            $logoFile = './personnalisation/'.$imageDirectory.'/logos/'.Settings::get('logo');
            $pdfData['logo'] = is_file(dirname(__DIR__, 4).'/'.$logoFile) ? $logoFile : '';
            $pdfData['id'] = $id;
            $pdfData['versionCache'] = $d['versionCache'] ?? '';

            require_once dirname(__DIR__, 4).'/vendor/tecnickcom/tcpdf/tcpdf.php';
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('Reservation '.$id);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(PDF_MARGIN_LEFT, 1, PDF_MARGIN_RIGHT);
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();

            $html = $this->twig->render('pdfconfirmation.twig', [
                'd' => $pdfData,
                'settings' => $context['AllSettings'] ?? [],
            ]);
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->lastPage();
            $content = $pdf->Output('reservation'.$id.'.pdf', 'S');
            $headers = $bootstrap['headers'];
            $headers['Content-Type'] = 'application/pdf';
            $headers['Content-Disposition'] = 'inline; filename="reservation'.$id.'.pdf"';

            return new Response($content, Response::HTTP_OK, $headers);
        }

        if (!$request->query->has('id')) {
            return $this->redirect((string) Settings::get('grr_url'), $bootstrap);
        }

        $id = $request->query->getInt('id');
        if ($id < 1) {
            throw new BadRequestHttpException('The "id" parameter must be a positive integer.');
        }

        $result = grr_sql_query('SELECT * FROM '.TABLE_PREFIX.'_entry WHERE id='.$id);
        if (!$result) {
            throw new \RuntimeException('Unable to load reservation details: '.grr_sql_error());
        }
        $row = grr_sql_row($result, 0);
        grr_sql_free($result);
        if (!$row) {
            throw new NotFoundHttpException('The requested reservation does not exist.');
        }

        $roomResult = grr_sql_query('SELECT room_name FROM '.TABLE_PREFIX.'_room WHERE id='.(int) $row[5]);
        if (!$roomResult) {
            throw new \RuntimeException('Unable to load the reservation resource: '.grr_sql_error());
        }
        $room = grr_sql_row($roomResult, 0);
        grr_sql_free($roomResult);
        if (!$room) {
            throw new NotFoundHttpException('The reservation resource does not exist.');
        }

        $data = [
            'id' => $id,
            'cle' => $row[18],
            'dossierImg' => $context['gcDossierImg'] ?? '',
            'organisme' => $row[10],
            'salle' => $room[0],
            'datedemande' => utf8_strftime($context['dformat'], strtotime($row[6])),
            'jour' => utf8_strftime($context['dformat'], $row[1]),
            'heure' => utf8_strftime('%H:%M', $row[1]),
            'heure2' => utf8_strftime('%H:%M', $row[2]),
            'jour2' => utf8_strftime($context['dformat'], $row[2]),
            'period' => 0,
            'rep_end_date' => 0,
            'jourPeriode' => 0,
        ];

        if ($row[4] != 0) {
            $repeatResult = grr_sql_query(
                'SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM '.TABLE_PREFIX.'_repeat WHERE id='.(int) $row[4]
            );
            if (!$repeatResult) {
                throw new \RuntimeException('Unable to load reservation recurrence details: '.grr_sql_error());
            }
            if (grr_sql_count($repeatResult) === 1) {
                $repeat = grr_sql_row($repeatResult, 0);
                $data['rep_end_date'] = utf8_strftime($context['dformat'], $repeat[1]);
                $data['period'] = 1;
                $data['jourPeriode'] = utf8_strftime('%A', $repeat[4]);
            }
            grr_sql_free($repeatResult);
        }

        return new Response(
            $this->twig->render('pdfgenerator.twig', [
                'd' => $data,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }

    private function redirect(string $target, array $bootstrap): RedirectResponse
    {
        return new RedirectResponse($target, Response::HTTP_FOUND, $this->captureHeaders($bootstrap['headers']));
    }

    private function captureHeaders(array $headers): array
    {
        foreach (headers_list() as $headerLine) {
            $separator = strpos($headerLine, ':');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($headerLine, 0, $separator));
            $headers[$name][] = trim(substr($headerLine, $separator + 1));
        }
        header_remove();

        return $headers;
    }
}
