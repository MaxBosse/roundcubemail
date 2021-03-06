<?php

namespace Tests\Browser\Settings;

use Tests\Browser\Components\App;

class Folders extends \Tests\Browser\TestCase
{
    public static function setUpBeforeClass()
    {
        \bootstrap::init_imap();
        \bootstrap::reset_mailboxes();
    }

    /**
     * Test Folders UI
     */
    public function testFolders()
    {
        $this->browse(function ($browser) {
            $browser->go('settings', 'folders');

            // task should be set to 'settings' and action to 'folders'
            $browser->with(new App(), function ($browser) {
                $browser->assertEnv('task', 'settings');
                $browser->assertEnv('action', 'folders');

                // these objects should be there always
                $browser->assertObjects(['quotadisplay', 'subscriptionlist']);
            });

            if ($browser->isDesktop()) {
                $browser->assertVisible('#settings-menu li.folders.selected');
            }

            if ($browser->isPhone()) {
                $browser->assertVisible('.floating-action-buttons a.create:not(.disabled)');
            }
            else {
                $browser->assertMissing('.floating-action-buttons a.create:not(.disabled)');
            }

            // Toolbar menu
            $browser->assertToolbarMenu(['create'], ['delete', 'purge']);

            // Folders list
            $browser->with('#subscription-table', function ($browser) {
                $browser->assertElementsCount('li', 1)
                    ->assertVisible('li.mailbox.inbox')
                    ->assertSeeIn('li.mailbox.inbox', 'Inbox')
                    ->assertPresent('li [type=checkbox][disabled]');
            });
        });
    }

    /**
     * Test folder creation
     */
    public function testFolderCreate()
    {
        $this->browse(function ($browser) {
            $browser->go('settings', 'folders');

            if ($browser->isPhone()) {
                $browser->assertVisible('.floating-action-buttons a.create:not(.disabled)')
                    ->click('.floating-action-buttons a.create')
                    ->waitFor('#preferences-frame');
            }
            else {
                $browser->clickToolbarMenuItem('create');
            }

            $browser->withinFrame('#preferences-frame', function($browser) {
                $browser->waitFor('form')
                    ->with('form fieldset', function ($browser) {
                        $browser->assertVisible('input[name=_name]')
                            ->assertValue('input[name=_name]', '')
                            ->assertVisible('select[name=_parent]')
                            ->assertSelected('select[name=_parent]', '');
                    })
/*
                    ->with('form fieldset:last-child', function ($browser) {
                        $browser->assertSeeIn('legend', 'Settings')
                            ->assertVisible('select[name=_viewmode]')
                            ->assertSelected('select[name=_viewmode]', '0');
                    })
*/
                    ->type('input[name=_name]', 'Test');

                if (!$browser->isPhone()) {
                    $browser->click('.formbuttons button.submit');
                }
            });

            if ($browser->isPhone()) {
                $browser->assertVisible('#layout-content .header a.back-list-button')
                    ->assertVisible('#layout-content .footer .buttons a.button.submit')
                    ->click('#layout-content .footer .buttons a.button.submit')
                    ->waitFor('#subscription-table');
            }
            else {
                $browser->waitForMessage('confirmation', 'Folder created successfully.');
            }

            $browser->closeMessage('confirmation');

            // Folders list
            $browser->with('#subscription-table', function ($browser) {
                // Note: li.root is hidden in Elastic
                $browser->waitFor('li.mailbox:nth-child(3)')
                    ->assertElementsCount('li', 2)
                    ->assertPresent('li.mailbox:nth-child(3) [type=checkbox]:not([disabled])')
                    ->click('li.mailbox:nth-child(3)');
            });

            if ($browser->isPhone()) {
                $browser->waitFor('#preferences-frame');
            }

            $browser->withinFrame('#preferences-frame', function($browser) {
                $browser->waitFor('form');
                // TODO
            });

            // Test unsubscribe of the newly created folder
            if ($browser->isPhone()) {
                $browser->click('a.back-list-button')
                    ->waitFor('#subscription-table');
            }

            $browser->setCheckboxState('#subscription-table li:nth-child(3) input', false)
                ->waitForMessage('confirmation', 'Folder successfully unsubscribed.');
        });
    }
}
