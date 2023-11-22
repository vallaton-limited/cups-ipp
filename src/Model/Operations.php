<?php

namespace Smalot\Cups\Model;

use Smalot\Cups\CupsException;

class Operations
{
    const PRINT_JOB = 0x0002;
    const PRINT_URI = 0x0003;
    const VALIDATE_JOB = 0x0004;
    const CREATE_JOB = 0x0005;
    const SEND_DOCUMENT = 0x0006;
    const SEND_URI = 0x0007;
    const CANCEL_JOB = 0x0008;
    const GET_JOB_ATTRIBUTES = 0x0009;
    const GET_JOBS = 0x000A;
    const GET_PRINTER_ATTRIBUTES = 0x000B;
    const HOLD_JOB = 0x000C;
    const RELEASE_JOB = 0x000D;
    const RESTART_JOB = 0x000E;
    const PAUSE_PRINTER = 0x0010;
    const RESUME_PRINTER = 0x0011;
    const PURGE_JOBS = 0x0012;
    const SET_PRINTER_ATTRIBUTES = 0x0013;
    const SET_JOB_ATTRIBUTES = 0x0014;
    const GET_PRINTER_SUPPORTED_VALUES = 0x0015;
    const CREATE_PRINTER_SUBSCRIPTIONS = 0x0016;
    const CREATE_JOB_SUBSCRIPTIONS = 0x0017;
    const GET_SUBSCRIPTION_ATTRIBUTES = 0x0018;
    const GET_SUBSCRIPTIONS = 0x0019;
    const RENEW_SUBSCRIPTION = 0x001A;
    const CANCEL_SUBSCRIPTION = 0x001B;
    const GET_NOTIFICATION = 0x001C;
    const GET_RESOURCE_ATTRIBUTES = 0x001E;
    const GET_RESOURCES = 0x0020;
    const ENABLE_PRINTER = 0x0022;
    const DISABLE_PRINTER = 0x0023;
    const PAUSE_PRINTER_AFTER_CURRENT_JOB = 0x0024;
    const HOLD_NEW_JOBS = 0x0025;
    const RELEASE_HELD_NEW_JOBS = 0x0026;
    const DEACTIVATE_PRINTER = 0x0027;
    const ACTIVATE_PRINTER = 0x0028;
    const RESTART_PRINTER = 0x0029;
    const SHUTDOWN_PRINTER = 0x002A;
    const STARTUP_PRINTER = 0x002B;
    const REPROCESS_JOB = 0x002C;
    const CANCEL_CURRENT_JOB = 0x002D;
    const SUSPEND_CURRENT_JOB = 0x002E;
    const RESUME_JOB = 0x002F;
    const PROMOTE_JOB = 0x0030;
    const SCHEDULE_JOB_AFTER = 0x0031;
    const CANCEL_DOCUMENT = 0x0033;
    const GET_DOCUMENT_ATTRIBUTES = 0x0034;
    const GET_DOCUMENTS = 0x0035;
    const DELETE_DOCUMENT = 0x0036;
    const SET_DOCUMENT_ATTRIBUTES = 0x0037;
    const CANCEL_JOBS = 0x0038;
    const CANCEL_MY_JOBS = 0x0039;
    const RESUBMIT_JOB = 0x003A;
    const CLOSE_JOB = 0x003B;
    const IDENTIFY_PRINTER = 0x003C;
    const VALIDATE_DOCUMENT = 0x003D;
    const ADD_DOCUMENT_IMAGES = 0x003E;
    const ACKNOWLEDGE_DOCUMENT = 0x003F;
    const ACKNOWLEDGE_IDENTIFY_PRINTER = 0x0040;
    const ACKNOWLEDGE_JOB = 0x0041;
    const FETCH_DOCUMENT = 0x0042;
    const FETCH_JOB = 0x0043;
    const GET_OUTPUT_DEVICE_ATTRIBUTES = 0x0044;
    const UPDATE_ACTIVE_JOBS = 0x0045;
    const DEREGISTER_OUTPUT_DEVICE = 0x0046;
    const UPDATE_DOCUMENT_STATUS = 0x0047;
    const UPDATE_JOB_STATUS = 0x0048;
    const UPDATE_OUTPUT_DEVICE_ATTRIBUTES = 0x0049;
    const GET_NEXT_DOCUMENT_DATA = 0x004A;
    const ALLOCATE_PRINTER_RESOURCES = 0x004B;
    const CREATE_PRINTER = 0x004C;
    const DEALLOCATE_PRINTER_RESOURCES = 0x004D;
    const DELETE_PRINTER = 0x004E;
    const GET_PRINTERS = 0x004F;
    const SHUTDOWN_ONE_PRINTER = 0x0050;
    const STARTUP_ONE_PRINTER = 0x0051;
    const CANCEL_RESOURCE = 0x0052;
    const CREATE_RESOURCE = 0x0053;
    const INSTALL_RESOURCE = 0x0054;
    const SEND_RESOURCE_DATA = 0x0055;
    const SET_RESOURCE_ATTRIBUTES = 0x0056;
    const CREATE_RESOURCE_SUBSCRIPTIONS = 0x0057;
    const CREATE_SYSTEM_SUBSCRIPTIONS = 0x0058;
    const DISABLE_ALL_PRINTERS = 0x0059;
    const ENABLE_ALL_PRINTERS = 0x005A;
    const GET_SYSTEM_ATTRIBUTES = 0x005B;
    const GET_SYSTEM_SUPPORTED_VALUES = 0x005C;
    const PAUSE_ALL_PRINTERS = 0x005D;
    const PAUSE_ALL_PRINTERS_AFTER_CURRENT_JOB = 0x005E;
    const REGISTER_OUTPUT_DEVICE = 0x005F;
    const RESTART_SYSTEM = 0x0060;
    const RESUME_ALL_PRINTERS = 0x0061;
    const SET_SYSTEM_ATTRIBUTES = 0x0062;
    const SHUTDOWN_ALL_PRINTERS = 0x0063;
    const STARTUP_ALL_PRINTERS = 0x0064;
    const GET_PRINTER_RESOURCES = 0x0065;
    const GET_USER_PRINTER_ATTRIBUTES = 0x0066;
    const RESTART_ONE_PRINTER = 0x0067;

    /**
     * Convert a command constant into a usable byte string
     *
     * @param $const
     *
     * @return string
     * @throws CupsException
     */
    public static function getCommandBytes($const): string
    {
        $parts = str_split(str_pad(dechex($const), 4,'0', STR_PAD_LEFT), 2);
        if (count($parts) === 2) {
            return chr(hexdec('0x'.$parts[0])) . chr(hexdec('0x'.$parts[1]));
        }

        throw new CupsException("Invalid command");
    }

    /**
     * Convert an integer representation of a supported operation into a string
     *
     * @param $identifier
     *
     * @return false|string
     */
    public static function getString($identifier)
    {
        $value = '';
        switch ($identifier) {
            case self::PRINT_JOB:
                $value = 'Print-Job';
                break;
            case self::PRINT_URI:
                $value = 'Print-URI';
                break;
            case self::VALIDATE_JOB:
                $value = 'Validate-Job';
                break;
            case self::CREATE_JOB:
                $value = 'Create-Job';
                break;
            case self::SEND_DOCUMENT:
                $value = 'Send-Document';
                break;
            case self::SEND_URI:
                $value = 'Send-URI';
                break;
            case self::CANCEL_JOB:
                $value = 'Cancel-Job';
                break;
            case self::GET_JOB_ATTRIBUTES:
                $value = 'Get-Job-Attributes';
                break;
            case self::GET_JOBS:
                $value = 'Get-Jobs';
                break;
            case self::GET_PRINTER_ATTRIBUTES:
                $value = 'Get-Printer-Attributes';
                break;
            case self::HOLD_JOB:
                $value = 'Hold-Job';
                break;
            case self::RELEASE_JOB:
                $value = 'Release-Job';
                break;
            case self::RESTART_JOB:
                $value = 'Restart-Job';
                break;
            case self::PAUSE_PRINTER:
                $value = 'Pause-Printer';
                break;
            case self::RESUME_PRINTER:
                $value = 'Resume-Printer';
                break;
            case self::PURGE_JOBS:
                $value = 'Purge-Jobs';
                break;
            case self::SET_PRINTER_ATTRIBUTES:
                $value = 'Set-Printer-Attributes'; // RFC3380
                break;
            case self::SET_JOB_ATTRIBUTES:
                $value = 'Set-Job-Attributes'; // RFC3380
                break;
            case self::GET_PRINTER_SUPPORTED_VALUES:
                $value = 'Get-Printer-Supported-Values'; // RFC3380
                break;
            case self::CREATE_PRINTER_SUBSCRIPTIONS:
                $value = 'Create-Printer-Subscriptions';
                break;
            case self::CREATE_JOB_SUBSCRIPTIONS:
                $value = 'Create-Job-Subscriptions';
                break;
            case self::GET_SUBSCRIPTION_ATTRIBUTES:
                $value = 'Get-Subscription-Attributes';
                break;
            case self::GET_SUBSCRIPTIONS:
                $value = 'Get-Subscriptions';
                break;
            case self::RENEW_SUBSCRIPTION:
                $value = 'Renew-Subscription';
                break;
            case self::CANCEL_SUBSCRIPTION:
                $value = 'Cancel-Subscription';
                break;
            case self::GET_NOTIFICATION:
                $value = 'Get-Notifications';
                break;
            case self::GET_RESOURCE_ATTRIBUTES:
                $value = 'Get-Resource-Attributes';
                break;
            case self::GET_RESOURCES:
                $value = 'Get-Resources';
                break;
            case self::ENABLE_PRINTER:
                $value = 'Enable-Printer';
                break;
            case self::DISABLE_PRINTER:
                $value = 'Disable-Printer';
                break;
            case self::PAUSE_PRINTER_AFTER_CURRENT_JOB:
                $value = 'Pause-Printer-After-Current-Job';
                break;
            case self::HOLD_NEW_JOBS:
                $value = 'Hold-New-Jobs';
                break;
            case self::RELEASE_HELD_NEW_JOBS:
                $value = 'Release-Held-New-Jobs';
                break;
            case self::DEACTIVATE_PRINTER:
                $value = 'Deactivate-Printer';
                break;
            case self::ACTIVATE_PRINTER:
                $value = 'Activate-Printer';
                break;
            case self::RESTART_PRINTER:
                $value = 'Restart-Printer';
                break;
            case self::SHUTDOWN_PRINTER:
                $value = 'Shutdown-Printer';
                break;
            case self::STARTUP_PRINTER:
                $value = 'Startup-Printer';
                break;
            case self::REPROCESS_JOB:
                $value = 'Reprocess-Job';
                break;
            case self::CANCEL_CURRENT_JOB:
                $value = 'Cancel-Current-Job';
                break;
            case self::SUSPEND_CURRENT_JOB:
                $value = 'Suspend-Current-Job';
                break;
            case self::RESUME_JOB:
                $value = 'Resume-Job';
                break;
            case self::PROMOTE_JOB:
                $value = 'Promote-Job';
                break;
            case self::SCHEDULE_JOB_AFTER:
                $value = 'Schedule-Job-After';
                break;
            case self::CANCEL_DOCUMENT:
                $value = 'Cancel-Document';
                break;
            case self::GET_DOCUMENT_ATTRIBUTES:
                $value = 'Get-Document-Attributes';
                break;
            case self::GET_DOCUMENTS:
                $value = 'Get-Documents';
                break;
            case self::DELETE_DOCUMENT:
                $value = 'Delete-Document';
                break;
            case self::SET_DOCUMENT_ATTRIBUTES:
                $value = 'Set-Document-Attributes';
                break;
            case self::CANCEL_JOBS:
                $value = 'Cancel-Jobs';
                break;
            case self::CANCEL_MY_JOBS:
                $value = 'Cancel-My-Jobs';
                break;
            case self::RESUBMIT_JOB:
                $value = 'Resubmit-Job';
                break;
            case self::CLOSE_JOB:
                $value = 'Close-Job';
                break;
            case self::IDENTIFY_PRINTER:
                $value = 'Identify-Printer';
                break;
            case self::VALIDATE_DOCUMENT:
                $value = 'Validate-Document';
                break;
            case self::ADD_DOCUMENT_IMAGES:
                $value = 'Add-Document-Images';
                break;
            case self::ACKNOWLEDGE_DOCUMENT:
                $value = 'Acknowledge-Document';
                break;
            case self::ACKNOWLEDGE_IDENTIFY_PRINTER:
                $value = 'Acknowledge-Identify-Printer';
                break;
            case self::ACKNOWLEDGE_JOB:
                $value = 'Acknowledge-Job';
                break;
            case self::FETCH_DOCUMENT:
                $value = 'Fetch-Document';
                break;
            case self::FETCH_JOB:
                $value = 'Fetch-Job';
                break;
            case self::GET_OUTPUT_DEVICE_ATTRIBUTES:
                $value = 'Get-Output-Device-Attributes';
                break;
            case self::UPDATE_ACTIVE_JOBS:
                $value = 'Update-Active-Jobs';
                break;
            case self::DEREGISTER_OUTPUT_DEVICE:
                $value = 'Deregister-Output-Device';
                break;
            case self::UPDATE_DOCUMENT_STATUS:
                $value = 'Update-Document-Status';
                break;
            case self::UPDATE_JOB_STATUS:
                $value = 'Update-Job-Status';
                break;
            case self::UPDATE_OUTPUT_DEVICE_ATTRIBUTES:
                $value = 'Update-Output-Device-Attributes';
                break;
            case self::GET_NEXT_DOCUMENT_DATA:
                $value = 'Get-Next-Document-Data';
                break;
            case self::ALLOCATE_PRINTER_RESOURCES:
                $value = 'Allocate-Printer-Resources';
                break;
            case self::CREATE_PRINTER:
                $value = 'Create-Printer';
                break;
            case self::DEALLOCATE_PRINTER_RESOURCES:
                $value = 'Deallocate-Printer-Resources';
                break;
            case self::DELETE_PRINTER:
                $value = 'Delete-Printer';
                break;
            case self::GET_PRINTERS:
                $value = 'Get-Printers';
                break;
            case self::SHUTDOWN_ONE_PRINTER:
                $value = 'Shutdown-One-Printer';
                break;
            case self::STARTUP_ONE_PRINTER:
                $value = 'Startup-One-Printer';
                break;
            case self::CANCEL_RESOURCE:
                $value = 'Cancel-Resource';
                break;
            case self::CREATE_RESOURCE:
                $value = 'Create-Resource';
                break;
            case self::INSTALL_RESOURCE:
                $value = 'Install-Resource';
                break;
            case self::SEND_RESOURCE_DATA:
                $value = 'Send-Resource-Data';
                break;
            case self::SET_RESOURCE_ATTRIBUTES:
                $value = 'Set-Resource-Attributes';
                break;
            case self::CREATE_RESOURCE_SUBSCRIPTIONS:
                $value = 'Create-Resource-Subscriptions';
                break;
            case self::CREATE_SYSTEM_SUBSCRIPTIONS:
                $value = 'Create-System-Subscriptions';
                break;
            case self::DISABLE_ALL_PRINTERS:
                $value = 'Disable-All-Printers';
                break;
            case self::ENABLE_ALL_PRINTERS:
                $value = 'Enable-All-Printers';
                break;
            case self::GET_SYSTEM_ATTRIBUTES:
                $value = 'Get-System-Attributes';
                break;
            case self::GET_SYSTEM_SUPPORTED_VALUES:
                $value = 'Get-System-Supported-Values';
                break;
            case self::PAUSE_ALL_PRINTERS:
                $value = 'Pause-All-Printers';
                break;
            case self::PAUSE_ALL_PRINTERS_AFTER_CURRENT_JOB:
                $value = 'Pause-All-Printers-After-Current-Job';
                break;
            case self::REGISTER_OUTPUT_DEVICE:
                $value = 'Register-Output-Device';
                break;
            case self::RESTART_SYSTEM:
                $value = 'Restart-System';
                break;
            case self::RESUME_ALL_PRINTERS:
                $value = 'Resume-All-Printers';
                break;
            case self::SET_SYSTEM_ATTRIBUTES:
                $value = 'Set-System-Attributes';
                break;
            case self::SHUTDOWN_ALL_PRINTERS:
                $value = 'Shutdown-All-Printers';
                break;
            case self::STARTUP_ALL_PRINTERS:
                $value = 'Startup-All-Printers';
                break;
            case self::GET_PRINTER_RESOURCES:
                $value = 'Get-Printer-Resources';
                break;
            case self::GET_USER_PRINTER_ATTRIBUTES:
                $value = 'Get-User-Printer-Attributes';
                break;
            case self::RESTART_ONE_PRINTER:
                $value = 'Restart-One-Printer';
                break;
            default:
                return false;
        }

        return $value;
    }
}
