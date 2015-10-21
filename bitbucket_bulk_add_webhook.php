<?php
class CONFIG
{
    /** Bitbucket user, used to authenticate. */
    CONST BITBUCKET_USER = 'BigBangBusiness';

    /** Bitbucket user password, used to authenticate. */
    CONST BITBUCKET_PASSWORD = 'yd8ajd82hd';

    /** Bitbucket account that holds the repositories. Most of time it's the same as BITBUCKET_USER. */
    CONST BITBUCKET_ACCOUNT = 'BigBangBusiness';

    /** Name of the webhook to be added. */
    CONST WEBHOOK_NAME = 'Webhook Example';

    /** Url of the webhook to be added. */
    CONST WEBHOOK_URL = 'http://example.com/webhook/B0CKKFJMA';

    /** Whether to delete all webhooks of each repository before adding this one. */
    CONST DELETE_ALL_PREVIOUS_WEBHOOKS = false;
}

require_once __DIR__.'/vendor/autoload.php';

header("Content-Type: text/plain");

$user = new Bitbucket\API\User();
$user->setCredentials( new Bitbucket\API\Authentication\Basic(CONFIG::BITBUCKET_USER, CONFIG::BITBUCKET_PASSWORD) );

$repos = $user->repositories()->dashboard();

$reposArray = json_decode($repos->getContent());

$slugsArray = array();

if (isset($reposArray[0]) && isset($reposArray[0][1]) && is_array($reposArray[0][1]))
{
    foreach($reposArray[0][1] as $repo)
    {
        array_push($slugsArray, $repo->slug);
    }
}

$webhooks  = new Bitbucket\API\Repositories\Hooks();

$webhooks->setCredentials( new Bitbucket\API\Authentication\Basic(CONFIG::BITBUCKET_USER, CONFIG::BITBUCKET_PASSWORD) );

echo "Adding webhook " . CONFIG::WEBHOOK_NAME . " to " . count($slugsArray) . " repositories from " . CONFIG::BITBUCKET_ACCOUNT . ":" . PHP_EOL;

foreach ($slugsArray as $slug)
{
    $webhookAlreadyAdded = false;

    $repositoryWebhooks = json_decode($webhooks->all(CONFIG::BITBUCKET_ACCOUNT, $slug)->getContent());
    if (isset($repositoryWebhooks->values))
    {
        if (CONFIG::DELETE_ALL_PREVIOUS_WEBHOOKS)
        {
            foreach($repositoryWebhooks->values as $currentWebhook)
            {
                $webhooks->delete(CONFIG::BITBUCKET_ACCOUNT, $slug, $currentWebhook->uuid);
                echo "[Webhook Deleted] $currentWebhook->description ($currentWebhook->url) from " . CONFIG::BITBUCKET_ACCOUNT . "/$slug". PHP_EOL;
            }
        }
        else
        {
            foreach($repositoryWebhooks->values as $currentWebhook)
            {
                if ($currentWebhook->description == CONFIG::WEBHOOK_NAME && $currentWebhook->url == CONFIG::WEBHOOK_URL && $currentWebhook->active)
                {
                    $webhookAlreadyAdded = true;
                    echo "[Not Needed] Webhook already installed on " . CONFIG::BITBUCKET_ACCOUNT . "/$slug" . PHP_EOL;
                    break;
                }
            }
        }
    }

    if (!$webhookAlreadyAdded)
    {
        $webhooks->create(CONFIG::BITBUCKET_ACCOUNT, $slug, array(
            'description' => CONFIG::WEBHOOK_NAME,
            'url' => CONFIG::WEBHOOK_URL,
            'active' => true,
            'events' => array(
                'repo:push',
            )
        ));
        echo "[OK] " . CONFIG::BITBUCKET_ACCOUNT . "/$slug" . PHP_EOL;
    }
}