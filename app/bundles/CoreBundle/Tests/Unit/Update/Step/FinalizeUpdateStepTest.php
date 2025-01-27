<?php

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Update\Step\FinalizeUpdateStep;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinalizeUpdateStepTest extends AbstractStepTest
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    /**
     * @var MockObject|PathsHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $pathsHelper;

    /**
     * @var MockObject|Session
     */
    private \PHPUnit\Framework\MockObject\MockObject $session;

    /**
     * @var MockObject|AppVersion
     */
    private \PHPUnit\Framework\MockObject\MockObject $appVersion;

    private \Mautic\CoreBundle\Update\Step\FinalizeUpdateStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->pathsHelper = $this->createMock(PathsHelper::class);
        $this->session     = $this->createMock(Session::class);
        $this->appVersion  = $this->createMock(AppVersion::class);

        $this->step = new FinalizeUpdateStep($this->translator, $this->pathsHelper, $this->session, $this->appVersion);
    }

    public function testFinalizationCleansUpFiles(): void
    {
        file_put_contents(__DIR__.'/resources/upgrade.php', '');
        file_put_contents(__DIR__.'/resources/lastUpdateCheck.txt', '');

        $wrappingUpKey       = 'mautic.core.update.step.wrapping_up';
        $updateSuccessfulKey = 'mautic.core.update.update_successful';

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                [$wrappingUpKey],
                [$updateSuccessfulKey, ['%version%' => '10.0.0']]
            )
            ->willReturnOnConsecutiveCalls(
                $wrappingUpKey,
                $updateSuccessfulKey
            );

        $this->pathsHelper->expects($this->once())
            ->method('getRootPath')
            ->willReturn(__DIR__.'/resources');

        $this->pathsHelper->expects($this->once())
            ->method('getCachePath')
            ->willReturn(__DIR__.'/resources');

        $this->appVersion->expects($this->once())
            ->method('getVersion')
            ->willReturn('10.0.0');

        $this->step->execute($this->progressBar, $this->input, $this->output);

        $this->assertFileDoesNotExist(__DIR__.'/resources/upgrade.php');
        $this->assertFileDoesNotExist(__DIR__.'/resources/lastUpdateCheck.txt');

        $this->assertEquals($updateSuccessfulKey, trim($this->progressBar->getMessage()));
    }

    public function testFinalizationWithPostUpgradeMessage(): void
    {
        file_put_contents(__DIR__.'/resources/upgrade.php', '');
        file_put_contents(__DIR__.'/resources/lastUpdateCheck.txt', '');

        $this->pathsHelper->expects($this->once())
            ->method('getRootPath')
            ->willReturn(__DIR__.'/resources');

        $this->pathsHelper->expects($this->once())
            ->method('getCachePath')
            ->willReturn(__DIR__.'/resources');

        $this->session->expects($this->once())
            ->method('get')
            ->with('post_upgrade_message')
            ->willReturn('This is an example message');

        $this->session->expects($this->once())
            ->method('remove');

        $this->output->expects($this->once())
            ->method('writeln')
            ->with("\n\n<info>This is an example message</info>");

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);

        $this->assertFileDoesNotExist(__DIR__.'/resources/upgrade.php');
        $this->assertFileDoesNotExist(__DIR__.'/resources/lastUpdateCheck.txt');
    }
}
