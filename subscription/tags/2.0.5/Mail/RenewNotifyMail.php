<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Mail;

use tiFy\Plugins\Subscription\QuerySubscription;
use tiFy\Mail\Mail as BaseMail;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Support\Proxy\{Partial, PostType};
use tiFy\Wordpress\Query\QueryPost as BaseQueryPost;

class RenewNotifyMail extends BaseMail
{
    use SubscriptionAwareTrait;

    /**
     * Instance de la commande associée.
     * @var QuerySubscription|null
     */
    protected $obj;

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        $data = [];
        $settings = $this->subscription()->settings();

        if ($url = $this->obj->getRenewUrl()) {
            $data['renew-link'] = Partial::get('tag', [
                'attrs'   => [
                    'class'         => 'Button--1',
                    'clicktracking' => 'off',
                    'href'          => $url,
                    'title'         => sprintf(
                        __('Renouvellement de votre %s', 'tify'),
                        PostType::get('subscription')->label('singular')
                    ),
                    'target'        => '_blank',
                ],
                'content' => sprintf(
                    __('Renouveler votre %s', 'tify'),
                    PostType::get('subscription')->label('singular')
                ),
                'tag'     => 'a',
            ]);
        }

        if (($id = $settings->getTermsOfUsePageId()) && ($post = BaseQueryPost::createFromId($id))) {
            $data['terms-of-use'] = Partial::get('tag', [
                'attrs'   => [
                    'clicktracking' => 'off',
                    'href'          => $post->getPermalink(),
                    'target'        => '_blank',
                    'title'         => sprintf(
                        __('Accéder à %s', 'tify'),
                        $post->getTitle()
                    ),
                ],
                'content' => $post->getTitle(),
                'tag'     => 'a',
            ]);
        }

        if (($id = $settings->getPrivacyPolicyPageId()) && ($post = BaseQueryPost::createFromId($id))) {
            $data['privacy-policy'] = Partial::get('tag', [
                'attrs'   => [
                    'clicktracking' => 'off',
                    'href'          => $post->getPermalink(),
                    'target'        => '_blank',
                    'title'         => sprintf(
                        __('Accéder à %s', 'tify'),
                        $post->getTitle()
                    ),
                ],
                'content' => $post->getTitle(),
                'tag'     => 'a',
            ]);
        }

        $data['expiration-date'] = $this->obj->getEndDate()->format('d/m/Y');
        $data['display_name'] = ($user = $this->obj->getCustomer()) ? $user->getDisplayName() : '';

        return array_merge(parent::defaults(), [
            'data'    => $data,
            'from'    => $this->subscription()->settings()->getRenewNotifySender(),
            'subject' => sprintf(
                __('[%s] >> Renouveler votre %s', 'tify'),
                get_bloginfo('blogname'), $this->obj->getType()->label('singular'),
            ),
            'to'      => $user->getEmail(),
            'viewer'  => [
                'override_dir' => $this->subscription()->resources('/views/mail/renew-notify'),
            ],
        ]);
    }

    /**
     * Définition de l'instance de la commande associée.
     *
     * @param QuerySubscription $obj
     *
     * @return $this
     */
    public function setObj(QuerySubscription $obj): self
    {
        $this->obj = $obj;

        return $this;
    }
}