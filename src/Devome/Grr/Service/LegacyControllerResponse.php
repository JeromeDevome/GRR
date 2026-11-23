<?php

namespace Devome\Grr\Service;

use Symfony\Component\HttpFoundation\Response;

final class LegacyControllerResponse
{
    /**
     * @param array{context: array<string, mixed>, headers: array<string, list<string>>, status: int} $bootstrap
     */
    public function capture(array $bootstrap, \Closure $page): Response
    {
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            $page();
        } finally {
            $content = '';
            while (ob_get_level() > $bufferLevel) {
                $captured = ob_get_clean();
                if ($captured !== false) {
                    $content = $captured.$content;
                }
            }
        }

        $headers = $bootstrap['headers'];
        foreach (headers_list() as $headerLine) {
            $separator = strpos($headerLine, ':');
            if ($separator === false) {
                continue;
            }

            $headers[trim(substr($headerLine, 0, $separator))][] = trim(substr($headerLine, $separator + 1));
        }

        $status = http_response_code();
        header_remove();
        if (is_int($status) && $status >= 100 && ($status !== Response::HTTP_OK || $bootstrap['status'] === Response::HTTP_OK)) {
            $bootstrap['status'] = $status;
        }

        return new Response(
            $content,
            $bootstrap['status'],
            $headers,
        );
    }
}
