<?php
namespace PHPJava\Compiler\Lang\Assembler\Traits\Enhancer\Operation;

use PHPJava\Compiler\Builder\Generator\Operation\Operand;
use PHPJava\Compiler\Builder\Generator\Operation\Operation;
use PHPJava\Compiler\Builder\Generator\Operation\ReplaceMarker;
use PHPJava\Compiler\Builder\Types\Int16;
use PHPJava\Compiler\Lang\Assembler\Enhancer\ConstantPoolEnhancer;
use PHPJava\Kernel\Maps\OpCode;

/**
 * @method ConstantPoolEnhancer getEnhancedConstantPool()
 */
trait Conditionable
{
    public function assembleConditions(array $ifStatementOperations, array $elseStatementOperations): array
    {
        $operations = [];

        // Decide start offset for finding.
        $startOffset = $this->calculateProgramCounterByOperationCodes(
            $operations
        );

        array_push(
            $operations,
            ...[
                ReplaceMarker::create(OpCode::_ifne, Int16::class),
            ],
            ...$ifStatementOperations,
            ...[
                ReplaceMarker::create(OpCode::_goto, Int16::class),
            ]
        );

        $programCounters = [
            $this->calculateProgramCounterByOperationCodes(
                $operations
            ) - $startOffset,
        ];

        array_push(
            $operations,
            ...$elseStatementOperations
        );

        $finallyPosition = $this->calculateProgramCounterByOperationCodes(
            $operations
        );

        // Replace markers
        $currentOffset = 0;
        foreach ($operations as &$operation) {
            /**
             * @var Operation|ReplaceMarker $operation
             */
            if (!($operation instanceof ReplaceMarker)) {
                continue;
            }

            $currentOffset = $this->calculateProgramCounterByOperationCodes(
                $operations,
                $operation->getOpCode(),
                $currentOffset
            );

            switch ($operation->getOpCode()) {
                case OpCode::_ifne:
                    $operation = Operation::create(
                        $operation->getOpCode(),
                        Operand::factory(
                            Int16::class,
                            array_shift($programCounters)
                        )
                    );
                    break;
                case OpCode::_goto:
                    $operation = Operation::create(
                        OpCode::_goto,
                        Operand::factory(
                            Int16::class,
                            $this->calculateProgramCounterByOperationCodes($elseStatementOperations) + 1 + 2
                        )
                    )->enableEffectiveProgramCounter(true);
                    break;
            }
        }
        return $operations;
    }
}
