<?php

namespace App\Console\Commands;

use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BatchUploadFile extends Command
{
    protected $s3Instance;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:batch_upload_file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch Upload Local File To S3';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->s3Instance = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION', 'us-west-2')
        ]);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //$this->uploadSingleFileToS3();
        $this->batchMultiUploadFileByS3();
    }


    public function batchMultiUploadFileByS3()
    {
        $time_start = microtime(true);

        try {
            $commands = $this->getLocalFiles()
                ->map(function ($value, $key) {
                    return $this->s3Instance->getCommand('PutObject', [
                        'Bucket' => env('AWS_BUCKET'),
                        'Key' => $key,
                        'Body' => $value,
                    ]);
                });

            CommandPool::batch($this->s3Instance, $commands);
        } catch (AwsException $ex) {
            error_log($ex->getMessage());
        }

        echo 'Total execution batch multi upload: ' . (microtime(true) - $time_start);
    }

    public function uploadSingleFileToS3()
    {
        $time_start = microtime(true);

        try {
            $this->getLocalFiles()
                ->each(function ($value, $key) {
                    $this->s3Instance->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key' => $key,
                        'Body' => $value,
                    ]);
                });
        } catch (AwsException $ex) {
            error_log($ex->getMessage());
        }

        echo 'Total execution single upload: ' . (microtime(true) - $time_start);
    }

    public function getLocalFiles()
    {
        return collect(Storage::disk('public')->allFiles())
            ->mapWithKeys(function ($fileName) {
                return [$fileName => Storage::disk('public')->get($fileName)];
            });
    }

}
