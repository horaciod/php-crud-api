<?php

namespace Tqdev\PhpCrudApi;

class Config
{
    private $values = [
        'driver' => null,
        'address' => null,
        'port' => null,
        'username' => '',
        'password' => '',
        'database' => '',
        'command' => '',
        'tables' => '',
        'mapping' => '',
        'middlewares' => 'cors,errors',
        'controllers' => 'records,geojson,openapi,status',
        'customControllers' => '',
        'customOpenApiBuilders' => '',
        'cacheType' => 'TempFile',
        'cachePath' => '',
        'cacheTime' => 10,
        'jsonOptions' => JSON_UNESCAPED_UNICODE,
        'debug' => false,
        'basePath' => '',
        'openApiBase' => '{"info":{"title":"PHP-CRUD-API","version":"1.0.0"}}',
    ];

    private function getDefaultDriver(array $values): string
    {
        if (isset($values['driver'])) {
            return $values['driver'];
        }
        return 'mysql';
    }

    private function getDefaultPort(string $driver): int
    {
        switch ($driver) {
            case 'mysql':
                return 3306;
            case 'pgsql':
                return 5432;
            case 'sqlsrv':
                return 1433;
            case 'sqlite':
                return 0;
        }
    }

    private function getDefaultAddress(string $driver): string
    {
        switch ($driver) {
            case 'mysql':
                return 'localhost';
            case 'pgsql':
                return 'localhost';
            case 'sqlsrv':
                return 'localhost';
            case 'sqlite':
                return 'data.db';
        }
    }

    private function getDriverDefaults(string $driver): array
    {
        return [
            'driver' => $driver,
            'address' => $this->getDefaultAddress($driver),
            'port' => $this->getDefaultPort($driver),
        ];
    }

    private function applyEnvironmentVariables(array $values, string $prefix = 'PHP_CRUD_API'): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $suffix = strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('.', '_', $key)));
            $newPrefix = $prefix . "_" . $suffix;
            if (is_array($value)) {
                $newPrefix = str_replace('PHP_CRUD_API_MIDDLEWARES_', 'PHP_CRUD_API_', $newPrefix);
                $result[$key] = $this->applyEnvironmentVariables($value, $newPrefix);
            } else {
                $result[$key] = getenv($newPrefix, true) ?: $value;
            }
        }
        return $result;
    }

    public function __construct(array $values)
    {
        $driver = $this->getDefaultDriver($values);
        $defaults = $this->getDriverDefaults($driver);
        $newValues = array_merge($this->values, $defaults, $values);
        $newValues['middlewares'] = getenv('PHP_CRUD_API_MIDDLEWARES', true) ?: $newValues['middlewares'];
        $newValues = $this->parseMiddlewares($newValues);
        $diff = array_diff_key($newValues, $this->values);
        if (!empty($diff)) {
            $key = array_keys($diff)[0];
            throw new \Exception("Config has invalid value '$key'");
        }
        $newValues = $this->applyEnvironmentVariables($newValues);
        $this->values = $newValues;
    }

    private function parseMiddlewares(array $values): array
    {
        $newValues = array();
        $properties = array();
        $middlewares = array_map('trim', explode(',', $values['middlewares']));
        foreach ($middlewares as $middleware) {
            $properties[$middleware] = [];
        }
        foreach ($values as $key => $value) {
            if (strpos($key, '.') === false) {
                $newValues[$key] = $value;
            } else {
                list($middleware, $key2) = explode('.', $key, 2);
                if (isset($properties[$middleware])) {
                    $properties[$middleware][$key2] = $value;
                } else {
                    throw new \Exception("Config has invalid value '$key'");
                }
            }
        }
        $newValues['middlewares'] = array_reverse($properties, true);
        return $newValues;
    }

    public function getDriver(): string
    {
        return $this->values['driver'];
    }

    public function getAddress(): string
    {
        return $this->values['address'];
    }

    public function getPort(): int
    {
        return $this->values['port'];
    }

    public function getUsername(): string
    {
        return $this->values['username'];
    }

    public function getPassword(): string
    {
        return $this->values['password'];
    }

    public function getDatabase(): string
    {
        return $this->values['database'];
    }

    public function getCommand(): string
    {
        return $this->values['command'];
    }


    public function getTables(): array
    {
        return array_filter(array_map('trim', explode(',', $this->values['tables'])));
    }

    public function getMapping(): array
    {
        $mapping = array_map(function ($v) {
            return explode('=', $v);
        }, array_filter(array_map('trim', explode(',', $this->values['mapping']))));
        return array_combine(array_column($mapping, 0), array_column($mapping, 1));
    }

    public function getMiddlewares(): array
    {
        return $this->values['middlewares'];
    }

    public function getControllers(): array
    {
        return array_filter(array_map('trim', explode(',', $this->values['controllers'])));
    }

    public function getCustomControllers(): array
    {
        return array_filter(array_map('trim', explode(',', $this->values['customControllers'])));
    }

    public function getCustomOpenApiBuilders(): array
    {
        return array_filter(array_map('trim', explode(',', $this->values['customOpenApiBuilders'])));
    }

    public function getCacheType(): string
    {
        return $this->values['cacheType'];
    }

    public function getCachePath(): string
    {
        return $this->values['cachePath'];
    }

    public function getCacheTime(): int
    {
        return $this->values['cacheTime'];
    }

    public function getJsonOptions(): int
    {
        return $this->values['jsonOptions'];
    }

    public function getDebug(): bool
    {
        return $this->values['debug'];
    }

    public function getBasePath(): string
    {
        return $this->values['basePath'];
    }

    public function getOpenApiBase(): array
    {
        return json_decode($this->values['openApiBase'], true);
    }
}
