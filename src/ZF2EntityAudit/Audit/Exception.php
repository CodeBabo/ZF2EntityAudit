<?php

namespace ZF2EntityAudit\Audit;

class Exception extends \Exception
{
    public static function notAudited($className)
    {
        return new self("Class '" . $className . "' is not audited.");
    }

    public static function noRevisionFound($className, $id, $revision)
    {
        return new self("No revision of class '" . $className . "' (".implode(", ", $id).") was found ".
            "at revision " . $revision . " or before. The entity did not exist at the specified revision yet.");
    }

    public static function invalidRevision($rev)
    {
        return new self("No revision '".$rev."' exists.");
    }

    public static function NotSupported()
    {
        return new self("ZF2EntityAudit Verion 0.2 doesn't support anonymous editing , please use `0.1-stable` for  anonymous editing");
    }
}
