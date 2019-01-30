<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Exceptions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\CatchHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatementHelper;
use function in_array;
use const T_CATCH;
use const T_OPEN_TAG;

class DeadCatchSniff implements Sniff
{

	public const CODE_CATCH_AFTER_THROWABLE_CATCH = 'CatchAfterThrowableCatch';

	/**
	 * @return (int|string)[]
	 */
	public function register(): array
	{
		return [
			T_CATCH,
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $catchPointer
	 */
	public function process(File $phpcsFile, $catchPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		/** @var int $openTagPointer */
		$openTagPointer = TokenHelper::findNext($phpcsFile, T_OPEN_TAG, 0);
		$catchToken = $tokens[$catchPointer];
		$catchedTypes = CatchHelper::findCatchedTypesInCatch(
			$phpcsFile,
			UseStatementHelper::getUseStatements($phpcsFile, $openTagPointer),
			$catchToken
		);

		if (!in_array('\\Throwable', $catchedTypes, true)) {
			return;
		}

		$nextCatchPointer = TokenHelper::findNextEffective($phpcsFile, $catchToken['scope_closer'] + 1);

		while ($nextCatchPointer !== null) {
			$nextCatchToken = $tokens[$nextCatchPointer];
			if ($nextCatchToken['code'] !== T_CATCH) {
				break;
			}

			$phpcsFile->addError('Unreachable catch block.', $nextCatchPointer, self::CODE_CATCH_AFTER_THROWABLE_CATCH);

			$nextCatchPointer = TokenHelper::findNextEffective($phpcsFile, $nextCatchToken['scope_closer'] + 1);
		}
	}

}
