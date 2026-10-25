<?php
declare(strict_types=1);

/**
 * Lỗi nghiệp vụ khi số chỗ còn lại không đủ cho kế hoạch.
 * Được ném trong transaction để rollback cả kế hoạch và các môn đã trừ chỗ.
 * Thử: gửi hai môn, môn thứ hai hết chỗ; giỏ và CSDL phải giữ nguyên.
 */
namespace App\Data;

final class MonKhongDuChoException extends \RuntimeException {}
