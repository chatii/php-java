<?php
namespace PHPJava\Compiler\Lang\Assembler\Traits\Enhancer\Operation;

use PHPJava\Compiler\Builder\Generator\Operation\Operand;
use PHPJava\Compiler\Builder\Generator\Operation\Operation;
use PHPJava\Compiler\Builder\Types\Uint16;
use PHPJava\Compiler\Builder\Types\Uint8;
use PHPJava\Compiler\Lang\Assembler\Enhancer\ConstantPoolEnhancer;
use PHPJava\Exceptions\AssembleStructureException;
use PHPJava\Kernel\Maps\OpCode;
use PHPJava\Utilities\ArrayTool;
use PHPJava\Utilities\Formatter;

/**
 * @method ConstantPoolEnhancer getEnhancedConstantPool()
 * @method array assembleLoadNumber(int $value, string &$type = null)
 */
trait FieldAssignable
{
    public function assembleAssignStaticField(string $className, string $fieldName, $value, string $descriptor): array
    {
        $operations = [];

        $parsedSignature = Formatter::parseSignature($descriptor);

        switch ($parsedSignature[0]['type']) {
            case 'class':
                $this->getEnhancedConstantPool()
                    ->addString($value);
                $operations[] = Operation::create(
                    OpCode::_ldc,
                    Operand::factory(
                        Uint8::class,
                        $this->getEnhancedConstantPool()
                            ->findString($value)
                    )
                );
                break;
            case 'int':
                ArrayTool::concat(
                    $operations,
                    ...$this->assembleLoadNumber(
                        $value
                    )
                );
                break;
            default:
                throw new AssembleStructureException(
                    'Unsupported signature type: ' . $descriptor
                );
        }

        $operations[] = Operation::create(
            OpCode::_putstatic,
            Operand::factory(
                Uint16::class,
                $this->getEnhancedConstantPool()
                    ->findField(
                        $className,
                        $fieldName,
                        $descriptor
                    )
            )
        );
        return $operations;
    }
}
