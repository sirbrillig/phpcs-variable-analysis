<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class ShortPhpTagsTest extends BaseTestCase
{
    public function testVariableWarningsWhenShortEchoTagsAreUsed()
    {
        $fixtureFile = $this->getFixture('ShortPhpTagsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
        $expectedWarnings = [
            4,
            7,
        ];
        $this->assertSame($expectedWarnings, $lines);
    }

    public function testVariableWarningsHaveCorrectSniffCodesWhenShortEchoTagsAreUsed()
    {
        $fixtureFile = $this->getFixture('ShortPhpTagsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$warnings = $phpcsFile->getWarnings();
        $this->assertSame(self::UNUSED_ERROR_CODE, $warnings[4][1][0]['source']);
        $this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[7][5][0]['source']);
    }
}
