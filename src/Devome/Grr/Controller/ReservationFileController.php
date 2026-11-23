<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Devome\Grr\Service\LegacyControllerResponse;
use Import;
use RuntimeException;
use SecuAccess;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ReservationFileController
{
    public function __construct(
        private readonly GrrApplicationContext $applicationContext,
        private readonly LegacyControllerResponse $response,
    ) {
    }

    public function manage(Request $request): Response
    {
        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        foreach ($context as $name => $value) {
            $GLOBALS[$name] = $value;
        }

        $action = $request->query->getInt('action', $request->request->getInt('action'));
        $fileId = $request->query->getInt('id', $request->request->getInt('id', -1));
        $entryId = $request->query->getInt('resa', $request->request->getInt('resa', -1));

        if (!in_array($action, [1, 2], true)) {
            throw new BadRequestHttpException('Une action valide est requise.');
        }
        if ($entryId < 1 || ($action === 2 && $fileId < 1)) {
            throw new BadRequestHttpException('Les identifiants de réservation et de fichier sont requis.');
        }

        $entry = mrbsGetEntryInfo($entryId);
        if (!$entry) {
            throw new NotFoundHttpException('La réservation est introuvable.');
        }
        if (!SecuAccess::IsAllowedToModifyResa(getUserName(), $entryId)) {
            throw new AccessDeniedHttpException();
        }

        if ($action === 1) {
            $this->populateLegacyUploads($request);
            $upload = $_FILES['myFiles']['name'] ?? null;
            if ($upload !== null) {
                $documentDirectory = $context['gcDossierDoc'] ?? '';
                if (!is_string($documentDirectory) || $documentDirectory === '' || basename($documentDirectory) !== $documentDirectory) {
                    throw new \RuntimeException('Le répertoire des documents de réservation est invalide.');
                }
                $uploadDirectory = dirname(__DIR__, 4).'/personnalisation/'.$documentDirectory;
                foreach ((array) $upload as $filename) {
                    if ($filename === '') {
                        continue;
                    }
                    if (
                        !is_string($filename)
                        || basename($filename) !== $filename
                        || str_contains($filename, "\0")
                        || str_contains($filename, '/')
                        || str_contains($filename, '\\')
                    ) {
                        throw new BadRequestHttpException('Le nom du fichier téléversé est invalide.');
                    }
                    if (file_exists($uploadDirectory.'/'.$filename)) {
                        throw new BadRequestHttpException('Un fichier portant ce nom existe déjà.');
                    }

                }
            }

            require_once dirname(__DIR__, 4).'/include/import.class.php';

            return $this->response->capture($bootstrap, function () use ($entryId): void {
                $result = Import::DocumentResa($entryId);
                $message = is_array($result) ? (string) ($result[0] ?? '') : '';
                echo $message;
            });
        }

        $result = grr_sql_query(
            'SELECT file_name, id_entry FROM '.TABLE_PREFIX.'_files WHERE id='.$fileId
        );
        if (!$result) {
            throw new \RuntimeException('Erreur de lecture en base de données.');
        }
        $file = grr_sql_row_keyed($result, 0);
        grr_sql_free($result);
        if (!$file || (int) $file['id_entry'] !== $entryId) {
            throw new NotFoundHttpException('Le fichier est introuvable pour cette réservation.');
        }

        $filename = (string) $file['file_name'];
        if ($filename === '' || basename($filename) !== $filename || str_contains($filename, "\0")) {
            throw new \RuntimeException('Le nom du fichier associé est invalide.');
        }

        $projectDir = dirname(__DIR__, 4);
        $documentDirectory = $context['gcDossierDoc'] ?? '';
        if (!is_string($documentDirectory) || $documentDirectory === '' || basename($documentDirectory) !== $documentDirectory) {
            throw new \RuntimeException('Le répertoire des documents de réservation est invalide.');
        }
        $uploadDirectory = realpath($projectDir.'/personnalisation/'.$documentDirectory);
        if ($uploadDirectory === false) {
            throw new \RuntimeException('Le répertoire des documents de réservation est introuvable.');
        }
        $filePath = $uploadDirectory.DIRECTORY_SEPARATOR.$filename;
        $resolvedFilePath = realpath($filePath);
        $isWithinUploadDirectory = $resolvedFilePath !== false
            && str_starts_with($resolvedFilePath, $uploadDirectory.DIRECTORY_SEPARATOR);
        $message = '';

        if ($resolvedFilePath !== false && $isWithinUploadDirectory && is_file($resolvedFilePath)) {
            if (!unlink($resolvedFilePath)) {
                $message = "Erreur, le fichier n'a pu être supprimé.";
            }
        } elseif ($resolvedFilePath === false) {
            $message = 'Le fichier n’existe pas, mise à jour de la base de données. ';
        } else {
            throw new \RuntimeException('Le chemin du fichier sort du répertoire autorisé.');
        }

        if ($message === '' || $resolvedFilePath === false) {
            $deleteResult = grr_sql_command(
                'DELETE FROM '.TABLE_PREFIX.'_files WHERE id='.$fileId.' AND id_entry='.$entryId
            );
            if ($deleteResult < 0) {
                $message .= 'Erreur de suppression dans la base de données.';
            } elseif ($resolvedFilePath !== false) {
                $message = 'Fichier supprimé.';
            } else {
                $message .= 'La base de données a été corrigée avec succès.';
            }
        }

        $_SESSION['displ_msg'] = 'yes';
        $_SESSION['session_message_error'] = $message;
        $location = 'semaine?'.http_build_query([
            'day' => (int) date('d', $entry['start_time']),
            'month' => (int) date('m', $entry['start_time']),
            'year' => (int) date('Y', $entry['start_time']),
            'room' => (int) $entry['room_id'],
            'msg' => $message,
        ]);

        return new RedirectResponse($location, Response::HTTP_FOUND, $bootstrap['headers']);
    }

    private function populateLegacyUploads(Request $request): void
    {
        if (isset($_FILES['myFiles'])) {
            return;
        }

        $uploads = $request->files->get('myFiles');
        if ($uploads === null) {
            return;
        }

        $uploads = is_array($uploads) ? array_values($uploads) : [$uploads];
        $uploads = array_values(array_filter($uploads, static fn ($upload): bool => $upload instanceof UploadedFile));
        if ($uploads === []) {
            return;
        }

        $_FILES['myFiles'] = [
            'name' => array_map(static fn (UploadedFile $upload): string => $upload->getClientOriginalName(), $uploads),
            'type' => array_map(static fn (UploadedFile $upload): string => $upload->getClientMimeType() ?? '', $uploads),
            'tmp_name' => array_map(static fn (UploadedFile $upload): string => $upload->getPathname(), $uploads),
            'error' => array_map(static fn (UploadedFile $upload): int => $upload->getError(), $uploads),
            'size' => array_map(static fn (UploadedFile $upload): int => $upload->getSize() ?? 0, $uploads),
        ];
    }
}
