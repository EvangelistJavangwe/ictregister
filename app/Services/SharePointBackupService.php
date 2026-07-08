<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SharePointBackupService
{
    private const CHUNK_SIZE = 327680 * 5; // ~1.56MB, must be a multiple of 320 KiB

    public function upload(string $localPath, string $remoteFilename): void
    {
        $token  = $this->getAccessToken();
        $siteId = config('services.sharepoint.site_id');
        $folder = trim(config('services.sharepoint.folder'), '/');
        $path   = rawurlencode($folder) . '/' . rawurlencode($remoteFilename);

        $size = filesize($localPath);
        if ($size === false) {
            throw new RuntimeException("Backup file not found: {$localPath}");
        }

        $sessionResponse = Http::withToken($token)
            ->post("https://graph.microsoft.com/v1.0/sites/{$siteId}/drive/root:/{$path}:/createUploadSession", [
                'item' => ['@microsoft.graph.conflictBehavior' => 'rename'],
            ]);

        if (!$sessionResponse->successful()) {
            throw new RuntimeException('Failed to start SharePoint upload session: ' . $sessionResponse->body());
        }

        $uploadUrl = $sessionResponse->json('uploadUrl');
        $handle    = fopen($localPath, 'rb');

        try {
            $offset = 0;
            while ($offset < $size) {
                $chunk      = fread($handle, self::CHUNK_SIZE);
                $chunkLen   = strlen($chunk);
                $rangeStart = $offset;
                $rangeEnd   = $offset + $chunkLen - 1;

                $chunkResponse = Http::withBody($chunk, 'application/octet-stream')
                    ->withHeaders([
                        'Content-Length' => (string) $chunkLen,
                        'Content-Range'  => "bytes {$rangeStart}-{$rangeEnd}/{$size}",
                    ])
                    ->put($uploadUrl);

                if (!$chunkResponse->successful()) {
                    throw new RuntimeException('SharePoint upload chunk failed: ' . $chunkResponse->body());
                }

                $offset += $chunkLen;
            }
        } finally {
            fclose($handle);
        }
    }

    private function getAccessToken(): string
    {
        $tenantId = config('services.sharepoint.tenant_id');

        $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
            'client_id'     => config('services.sharepoint.client_id'),
            'client_secret' => config('services.sharepoint.client_secret'),
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to authenticate with Microsoft Graph: ' . $response->body());
        }

        return $response->json('access_token');
    }
}
