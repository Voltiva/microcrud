<?php

namespace Microcrud\Abstracts\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Microcrud\Abstracts\Service;

class UpdateJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $service;
    /**
     * Create a new job instance.
     *
     * @param array $data
     * @return void
     */
    public function __construct($data, $service)
    {
        $this->service = new $service;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->service->update($this->data);
    }

    // public function failed(\Exception $e)
    // {
    //     \Illuminate\Support\Facades\Log::debug('MyNotification failed');
    // }
}
