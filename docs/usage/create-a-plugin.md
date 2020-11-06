# Ulrack AOP Extension - Create a plugin

With this package plugins can be made for existing classes. This is made
possible by generating a proxy for the class which handles the invocation on the
plugins. To accomplish this three configurations are required:
- `join-points` describe the point in the project where the plugin can be
registered
- `advices` describe how and which plugins are used.
- `pointcuts` describe the combination of a `join-point` and `advice`.

All of these configurations are managed through the services, so multiple
configurations can be declared per file.

## Join-points

Join-points describe the point on the class where plugins can be added. This
can only be done for non `__construct` public methods. In order to register a
`join-point`, create a directory under `configuration/join-points` with the
following contents:
```json
{
    "invoke-all-endpoints": {
        "class": "\\Ulrack\\Web\\Common\\Endpoint\\EndpointInterface",
        "method": "__invoke",
        "explicit": false
    },
    "invoke-my-endpoint": {
        "class": "\\MyVendor\\MyProject\\MyEndpoint",
        "method": "__invoke",
        "explicit": true
    },
    "invoke-home-endpoint": {
        "service": "services.default-home-endpoint",
        "method": "__invoke"
    },
}
```

Three different registrations are documented above. The first one is a
non-explicit class based declaration. This means that, when an `advice` is
registered to this `join-point` all classes which are of the type
`\Ulrack\\Web\Common\Endpoint\EndpointInterface` will have the plugins attached.

The second declaration is an explicit class based declaration. This means that
only the `advices` are added when the class is explicity the on declared in the
`class` field.

The third declaration is a service based declaration. This means that all
`advices` registered to this `join-point` are only added if the class is created
through that specific service invocation.

## Advices

Advices describe the usage of the plugins in the system. To register an advice,
create a file in the directory `configuration/advices` with the following
contents:

```json
{
    "foo-after": {
        "service": "services.advice.service",
        "hook": "after"
    },
    "foo-before": {
        "service": "services.advice.service",
        "hook": "before"
    },
    "foo-around": {
        "service": "services.advice.service",
        "hook": "around"
    }
}
```

The `service` field must reference a service which can be called to create
the plugin. The result must implement the interface
`\Ulrack\AopExtension\Common\PluginInterface`.

The `hook` field is used to described what on the plugin is called. The `before`
method can be used to alter parameters through a unified object. The `after`
method can be used to alter the return of the original method. The `around`
method can be used to alter both, and control the original invocation.

A simple plugin which adds a header to an endpoint would look like the
following example:
```php
<?php

namespace MyVendor\MyProject;

use GrizzIt\Storage\Common\StorageInterface;
use Ulrack\AopExtension\Common\PluginInterface;
use Ulrack\Web\Common\Endpoint\OutputInterface;

class MyPlugin implements PluginInterface
{
    /**
     * Invoked before the method invocation.
     *
     * @param StorageInterface $parameters
     * @param string $signature
     * @param mixed $subject
     *
     * @return void
     */
    public function before(
        StorageInterface $parameters,
        string $methodName,
        $subject
    ): void {
        /** @var OutputInterface $output */
        $output = $parameters->get('output');
        $output->setHeader('qux', microtime());
    }

    /**
     * Invoked after the method invocation.
     *
     * @param StorageInterface $parameters
     * @param string $signature
     * @param mixed $subject
     * @param mixed $return
     *
     * @return mixed
     */
    public function after(
        StorageInterface $parameters,
        string $methodName,
        $subject,
        $return
    ) {
        /** @var OutputInterface $output */
        $output = $parameters->get('output');
        $output->setHeader('baz', microtime());

        return $return;
    }

    /**
     * Invoked around the method invocation.
     *
     * @param StorageInterface $parameters
     * @param string $signature
     * @param mixed $subject
     * @param callable $proceed
     *
     * @return mixed
     */
    public function around(
        StorageInterface $parameters,
        string $methodName,
        $subject,
        callable $proceed
    ) {
        /** @var OutputInterface $output */
        $output = $parameters->get('output');
        $output->setHeader('foo', microtime());
        $return = $proceed();
        $output->setHeader('bar', microtime());

        return $return;
    }
}

```

## Pointcuts

The `pointcut` describes the combination of an `advice` and a `join-point`. In
order to register a `join-point` create a file in `configuration/pointcuts` with
the following content:
```json
{
    "invoke-foo-around-all-endpoints": {
        "join-point": "invoke-all-endpoints",
        "advice": "foo-around",
        "sortOrder": 500
    },
    "invoke-foo-before-my-endpoint": {
        "join-point": "invoke-my-endpoint",
        "advice": "foo-before"
    },
    "invoke-foo-after-home-endpoint": {
        "join-point": "invoke-home-endpoint",
        "advice": "foo-after"
    }
}
```

The `join-point` field contains the key of the referenced `join-point`.
The `advice` field contains the key of the referenced `advice`.
Optionally the `sortOrder` can be added to determine the placement in the chain.
The `sortOrder` will default to 1000.

## Chain of execution

Plugins are sorted by `sortOrder` and executed as follows:
- First the `before` plugins will be executed.
- Then the `around` plugins will be executed.
- And finally the `after` plugins are executed.

## Commands

The plugins are executed by wrapping the original class in a proxy, this proxy
gets the `plugins` injected into them by the advices and handles the execution.
These proxies are being cached in `var/generated`. These classes can be removed
to regenerate them (e.g. when the signature of the class changes). This can be
done manually, but also with the `bin/application aop clear` command.

When proxies are called, but don't exist, they are being generated on the fly.
To prevent this from happening mostly, the `bin/application aop generate`
command can be called. This will generate all proxies and combined configuration
for all `services.*` class combinations. For `invocations.*` the return type can
not be guaranteed based on configuration, so this is generated on the fly.

## Further reading

[Back to usage index](index.md)

[Installation](installation.md)
