<?php

namespace service;

use Exception;

class HubException extends Exception
{
    /** 2XX CODES */
    // success message
    const SUCCESS_CODE = 200;

    /** 4XX CODES */
    // token is expired
    const TE_CODE = 401;
    // token is not valid
    const TNV_CODE = 403;
    // web service not found
    const NF_CODE = 404;
    // parameter is wrong
    const PARAMETER_CODE = 420;
    // username is duplicate
    const UD_CODE = 421;
    // phone number format is not valid
    const PN_CODE = 422;
    // verify code format is not correct
    const VC_CODE = 423;
    // profile input fields are not correct
    const PIF_CODE = 424;
    // verify code is wrong
    const UVD_CODE = 425;
    // must be login
    const MBL_CODE = 426;
    // must be subscribe
    const MBS_CODE = 427;
    // new score is smaller
    const SIS_CODE = 430;
    //package not found
    const NAP_CODE = 431;

    /** 5XX ERRORS */
    // internal server error
    const ISE_CODE = 500;
    // update phone number failed
    const UPN_CODE = 520;
    // sms not send
    const SMS_CODE = 521;
    // http method is wrong
    const HM_CODE = 522;
    // find competition game failed
    const CG_CODE = 524;
    // find user by id failed
    const FUBI_CODE = 525;
    // find game by id failed
    const FGBI_CODE = 526;
    // find all games failed
    const FAG_CODE = 527;
    // find news by id failed
    const GON_CODE = 528;
    // update user profile failed
    const UP_CODE = 529;
    // leaderboard find user score failed
    const LFUS_CODE = 530;
    // leaderboard delete user failed
    const LDU_CODE = 531;
    // leaderboard upsert user failed
    const LUU_CODE = 532;
    // user decrement play count failed
    const DPC_CODE = 533;
    // user reset play count failed
    const RPC_CODE = 534;
    // upsert user score failed
    const UUS_CODE = 536;
    // update play count process failed
    const PCP_CODE = 537;
    // update last play time process
    const ULPT_CODE = 538;
    // user does not have play count
    const UPC_CODE = 539;
    // update coin failed
    const UC_CODE = 540;
    // user is ban
    const UB_CODE = 541;
    // user score save not valid (time formula)
    const UNS_CODE = 542;
    // user ban limit
    const UBL_CODE = 543;
    // user no have enough gem
    const NHG_CODE = 544;
    /** 8xx errors! shop errors */
    // package not available
    const PNA_CODE = 800;
    // low coins count
    const LCC_CODE = 801;

    const LGC_COUNT = 802;
    // No Save Score
    const NSS_CODE = 803;
    // safe delete user
    const SDU_CODE = 804;
    // Save After Finish Competition Time
    const SAFCT_CODE = 805;

    const UB_MESSAGE = 'حساب کاربری شما در این بازی مسدود شده است';
    const EMPTY_MESSAGE = '';
    const MBS_MESSAGE = 'لطفا ابتدا ثبت نام کنید';
    const MBL_MESSAGE = 'لطفا ثبت نام کنید';
    const TNV_MESSAGE = 'توکن معتبر نیست';
    const TE_MESSAGE = 'توکن منقضی شده است';
    const PIF_MESSAGE = 'موارد به درستی تکمیل شود';
    const VC_MESSAGE = 'فرمت کد تایید اشتباه است';
    const PN_MESSAGE = 'فرمت شماره تلفن اشتباه است';
    const UD_MESSAGE = 'نام کاربری تکراری است';
    const UVD_MESSAGE = 'کد تایید اشتباه است';
    const NF_MESSAGE = 'وب سرویس مورد نظر پیدا نشد';
    const PARAMETER_MESSAGE = 'پارامترهای ورودی اشتباه است';
    const ISE_MESSAGE = 'خطای داخلی سرور';
    const HM_MESSAGE = 'متد http اشتباه است';
    const UPC_MESSAGE = 'محدودیت روزانه بازی شما به اتمام رسیده و از این لحظه امتیاز شما در لیدربورد ثبت نخواهد شد';
    const NAP_MESSAGE = 'پکیج مورد نظر پیدا نشد.';
    const PNA_MESSAGE = 'خرید این بسته در حال حاضر ممکن نیست!';
    const LCC_MESSAGE = 'تعداد سکه های شما کافی نیست';
    const LGC_MESSAGE = 'تعداد الماس‌های شما کافی نیست.';
    const UNS_MESSAGE = 'امتیاز شما معتبر نیست';
    const UBL_MESSAGE = 'امتیاز شما معتبر نیست و حساب کاربری شما به مدست 30 دقیقه غیر فعال شد';
    const NHG_MESSAGE = 'تعداد جم شما کافی نیست';
    const NSS_MESSAGE = 'امتیاز شما نا معتبر است';
    const SDU_MESSAGE = 'not success';
    const SAFCT_MESSAGE = 'امکان ثبت امتیاز بعد از پایان مسابقه وجود ندارد';

    public function __construct($message = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        # catch exception
        $this->showError();
    }

    private function showError()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $this->getCode(), 'description' => $this->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}