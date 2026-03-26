<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\UploadedFile;

class S3Service
{
    private S3Client $client;
    private string $bucket;
    private string $region;
    private ?string $endpoint;

    public function __construct()
    {
        $config = config('filesystems.disks.s3');

        $this->bucket   = $config['bucket'];
        $this->region   = $config['region'];
        $this->endpoint = $config['endpoint'] ?? null;

        $clientConfig = [
            'version'     => 'latest',
            'region'      => $this->region,
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
        ];

        if ($this->endpoint) {
            $clientConfig['endpoint']                = $this->endpoint;
            $clientConfig['use_path_style_endpoint'] = true;
        }

        $this->client = new S3Client($clientConfig);
    }

    /**
     * Upload a file to S3 and return the public URL.
     *
     * @param UploadedFile $file The uploaded file instance from the request.
     * @param string       $path The full S3 key/path (e.g. "clinics/1/patients/42/photos/uuid.jpg").
     * @return string             The public URL of the uploaded object.
     *
     * @throws S3Exception on upload failure.
     */
    public function upload(UploadedFile $file, string $path): string
    {
        $result = $this->client->putObject([
            'Bucket'       => $this->bucket,
            'Key'          => $path,
            'Body'         => fopen($file->getRealPath(), 'rb'),
            'ContentType'  => $file->getMimeType(),
            'ACL'          => 'private',
            'Metadata'     => [
                'original-name' => $file->getClientOriginalName(),
                'uploaded-by'   => (string) (auth()->id() ?? 'system'),
            ],
        ]);

        // Return the canonical object URL
        return $result->get('ObjectURL')
            ?? "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$path}";
    }

    /**
     * Delete an object from S3 by its key.
     *
     * @param string $key The S3 object key to delete.
     * @return bool        True on success, false if the delete failed silently.
     */
    public function delete(string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            return true;
        } catch (S3Exception $e) {
            // Log but do not rethrow — caller decides how to handle
            \Illuminate\Support\Facades\Log::warning('S3Service::delete failed', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate a pre-signed URL granting temporary read access to a private object.
     *
     * @param string $key     The S3 object key.
     * @param int    $minutes Validity window in minutes (default 60).
     * @return string          The pre-signed URL string.
     */
    public function getSignedUrl(string $key, int $minutes = 60): string
    {
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$minutes} minutes");

        return (string) $request->getUri();
    }

    /**
     * Copy an existing S3 object to a new key within the same bucket.
     *
     * @param string $sourceKey      The source object key.
     * @param string $destinationKey The destination object key.
     * @return string                 The URL of the newly copied object.
     */
    public function copy(string $sourceKey, string $destinationKey): string
    {
        $this->client->copyObject([
            'Bucket'     => $this->bucket,
            'CopySource' => "/{$this->bucket}/{$sourceKey}",
            'Key'        => $destinationKey,
            'ACL'        => 'private',
        ]);

        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$destinationKey}";
    }

    /**
     * Check whether an object exists in S3 without downloading it.
     */
    public function exists(string $key): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            return true;
        } catch (S3Exception) {
            return false;
        }
    }
}
