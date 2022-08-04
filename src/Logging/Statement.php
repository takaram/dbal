<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Logging;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use Psr\Log\LoggerInterface;

final class Statement extends AbstractStatementMiddleware
{
    /** @var array<int,mixed>|array<string,mixed> */
    private array $params = [];

    /** @var array<int,ParameterType>|array<string,ParameterType> */
    private array $types = [];

    /**
     * @internal This statement can be only instantiated by its connection.
     */
    public function __construct(
        StatementInterface $statement,
        private readonly LoggerInterface $logger,
        private readonly string $sql
    ) {
        parent::__construct($statement);
    }

    /**
     * @deprecated Use {@see bindValue()} instead.
     */
    public function bindParam(
        int|string $param,
        mixed &$variable,
        ParameterType $type,
        ?int $length = null
    ): void {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5563',
            '%s is deprecated. Use bindValue() instead.',
            __METHOD__
        );

        $this->params[$param] = &$variable;
        $this->types[$param]  = $type;

        parent::bindParam($param, $variable, $type, $length);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        parent::bindValue($param, $value, $type);
    }

    public function execute(): ResultInterface
    {
        $this->logger->debug('Executing statement: {sql} (parameters: {params}, types: {types})', [
            'sql'    => $this->sql,
            'params' => $this->params,
            'types'  => $this->types,
        ]);

        return parent::execute();
    }
}
