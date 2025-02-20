<?php

namespace Lezhnev74\TikTokBusiness;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TikTokExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('tiktok_business', Provider::class);
    }
}
