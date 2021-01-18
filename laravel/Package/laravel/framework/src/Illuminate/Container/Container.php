<?php

namespace Illuminate\Container;

use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container
{
    protected $buildStack = []; //构建栈
    protected $with = [];

    /**
     * 生产实例
     * @param $abstract
     * @param array $parameter
     * @return mixed|object
     * @throws ReflectionException
     */
    public function make ($abstract, $parameter = [])
    {
        return $this->resolve($abstract, $parameter);
    }

    /**
     * 解析抽象
     * @param $abstract
     * @return mixed|object
     * @throws \ReflectionException
     */
    protected function resolve ($abstract, $parameter = [])
    {
        $concrete = $abstract;

        $this->with[] = $parameter;

        return $this->build($concrete);
    }

    /**
     * 构建实例
     * @param $concrete
     * @return mixed|object
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    protected function build ($concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $exception) {
            //第三个参数用来显示Stack trace:, 传入上一次异常
            throw new BindingResolutionException("没有找到实例[".$concrete."]", 0, $exception);
        }

        //判断实例是否可以实例化
        if (!$reflector->isInstantiable()) {
            $this->notInstantiable($concrete);
        }

        //将可以实例化的实例放入构建栈
        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        /**
         * 抛出异常, 并将该实例移除构建栈
         */
        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (BindingResolutionException $exception) {
            array_pop($this->buildStack);
            throw $exception;
        }


        array_pop($this->buildStack);

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
            /**
             * 判断依赖是否做了参数覆盖
             */
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }

            if (!$dependency->getClass()) {
                $this->resolvePrimitive($dependency);
            } else {
                $results[] = $this->make($dependency->getName());
            }

        }

        return $results;
    }

    /**
     * 如果参数是一个字符串或者其他基元类型时，不是我们想要的依赖，需要判断其是否有默认值，如果有默认值返回默认值，没有的话不是我们想要的依赖，容器抛
     * 出异常
     * @param $dependency
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function resolvePrimitive (ReflectionParameter $dependency)
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        $this->unresolvablePrimitive($dependency);
    }

    /**
     * 抛出依赖不能被解析的异常
     * @param ReflectionParameter $parameter
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive (ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * 获取依赖的覆盖参数
     */
    protected function getParameterOverride ($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * 判断依赖是否做了参数覆盖
     * @param $dependency
     */
    protected function hasParameterOverride ($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    protected function getLastParameterOverride ()
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * 如果类不能被实例化，则抛出异常
     * 不能被实例化的类：interface/abstract/private __construct/protected __construct/trait
     * @param $concrete
     * @throws BindingResolutionException
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack)) {
            $concrete = implode(',', $this->buildStack);
        }

        $message = "目标实例[$concrete]不能被实例化";

        throw new BindingResolutionException($message);
    }
}

/**
 * 实例化类遇到的问题：
 * 1、如果容器传入不存在的实例的时候，我们引入BindingResolutionException来自定义我们返回的异常信息
 * 2、如果容器传入不能被实例化的类时，我们使用反射类的isInstantiable方法来判断实例是否可以实例化，否则抛出异常
 * 3、引入构建栈，来查看未实例化的实例
 * 4、捕捉依赖解析抛出的异常
 *
 * 解析依赖遇到的问题：
 * 1、如果传入的依赖没有做约定，那么此时在使用反射类获取构造参数时，会把参数变量名作为类的名称去实例化并抛出异常，此时我们引入with参数，来覆盖未做约
 * 定的参数。
 * 2、如果传入的依赖是字符串或者其他不能被实例化的基元类型，我们引入resolvePrimitive来判断依赖是否有默认值，如果有的话，返回默认值，没有抛出异常
 */