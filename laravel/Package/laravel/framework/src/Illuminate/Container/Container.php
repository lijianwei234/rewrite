<?php

namespace Illuminate\Container;

use ReflectionClass;

class Container
{
    /**
     * 生产实例
     * @param $abstract
     * @return mixed|object
     * @throws \ReflectionException
     */
    public function make ($abstract)
    {
        return $this->resolve($abstract);
    }

    /**
     * 解析抽象
     * @param $abstract
     * @return mixed|object
     * @throws \ReflectionException
     */
    protected function resolve ($abstract)
    {
        $concrete = $abstract;

        return $this->build($concrete);
    }

    /**
     * 构建实例
     * @param $concrete
     * @return mixed|object
     * @throws \ReflectionException
     */
    protected function build ($concrete)
    {
        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * 解析依赖
     * @param $dependencies
     * @return array
     * @throws \ReflectionException
     */
    protected function resolveDependencies ($dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $results[] = $this->make($dependency->getName());
        }

        return $results;
    }
}