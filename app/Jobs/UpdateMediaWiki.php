<?php

namespace App\Jobs;

use App\Models\Reference;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMediaWiki implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Reference $reference)
    {
        $this->reference = $reference;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jar = new CookieJar;
        $client = new Client;

        // Get login token
        $response = $client->request('GET', config('services.wiki.endpoint'), [
            'query' => [
                'action' => 'query',
                'meta' => 'tokens',
                'type' => 'login',
                'format' => 'json',
            ],
            'cookies' => $jar,
        ]);

        $json = json_decode($response->getBody());
        $loginToken = $json->query->tokens->logintoken;

        // Login
        $response = $client->request('POST', config('services.wiki.endpoint'), [
            'query' => [
                'action' => 'login',
                'lgname' => config('services.wiki.username'),
                'format' => 'json',
            ],
            'form_params' => [
                'lgpassword' => config('services.wiki.password'),
                'lgtoken' => $loginToken,
            ],
            'cookies' => $jar,
        ]);

        // Get CSRF token
        $response = $client->request('GET', config('services.wiki.endpoint'), [
            'query' => [
                'action' => 'query',
                'meta' => 'tokens',
                'format' => 'json',
            ],
            'cookies' => $jar,
        ]);

        $json = json_decode($response->getBody());
        $csrfToken = $json->query->tokens->csrftoken;

        // Update page
        $response = $client->request('POST', config('services.wiki.endpoint'), [
            'query' => [
                'action' => 'edit',
                'title' => $this->reference->reference,
                'text' => $this->getPageContents(),
                'format' => 'json',
            ],
            'form_params' => [
                'token' => $csrfToken,
            ],
            'cookies' => $jar,
        ]);
    }

    /**
     * Return the Wiki page contents
     *
     * @return string
     */
    public function getPageContents()
    {
        $point = $this->reference->getETRS89Coordinates();

        return view('wiki.page', ['reference' => $this->reference, 'point' => $point])->render();
    }
}
