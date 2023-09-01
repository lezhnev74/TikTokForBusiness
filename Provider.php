<?php

namespace Lezhnev74\TikTokBusiness;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * https://business-api.tiktok.com/portal/docs?rid=w08mdqof6nq&id=1738373164380162
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TIKTOK_BUSINESS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [];

    /**
     * @var User
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $fields = [
            'app_id' => $this->clientId,
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
        ];

        $fields = array_merge($fields, $this->parameters);
        return 'https://business-api.tiktok.com/portal/auth?' . http_build_query($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        // https://business-api.tiktok.com/portal/docs?rid=w08mdqof6nq&id=1739965703387137
        $response = $this->getAccessTokenResponse($this->getCode());

        $token = Arr::get($response, 'data.access_token');
        $scopes = Arr::get($response, 'data.scope', []);

        $this->user = $this->mapUserToObject(
            $this->getUserByToken($token)
        );

        return $this->user->setToken($token)
            ->setApprovedScopes($scopes);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenUrl()
    {
        return 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/';
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
            RequestOptions::JSON => $this->getTokenFields($code),
        ]);

        \Log::debug(Message::toString($response));

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'app_id' => (string)$this->clientId,
            'secret' => (string)$this->clientSecret,
            'auth_code' => $code,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getUserByToken($token)
    {
        // https://business-api.tiktok.com/portal/docs?rid=w08mdqof6nq&id=1739665513181185
        $response = $this->getHttpClient()->get(
            'https://business-api.tiktok.com/open_api/v1.3/user/info/',
            [
                'headers' => [
                    'Access-Token' => $token,
                ],
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject($user)
    {
        $user = $user['data'];

        return (new User())->setRaw($user)->map([
            'id' => $user['core_user_id'],
            'name' => $user['display_name'],
            'avatar' => $user['avatar_url'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenHeaders($code)
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
