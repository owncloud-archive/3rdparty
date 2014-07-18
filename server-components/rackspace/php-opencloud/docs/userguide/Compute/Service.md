# Compute service

## Setup

To instantiate a Compute service object, you first need to setup a Rackspace/OpenStack client. To do this, or for more
information, please consult the [Clients documentation](../Clients.md).

```php
$service = $client->computeService();
```

If no arguments are provided to the above method, certain values are set to their default values:

|Param|Default value|
|---|---|
|`$name`|cloudServersOpenStack|
|`$region`|DFW|
|`$urltype`|publicURL|