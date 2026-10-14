<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;
use App\Repositories\BranchRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\RepairEventRepository;
use App\Repositories\RepairRepository;
use App\Services\CustomerResolver;
use App\Services\MailResult;
use App\Services\RepairMailer;
use App\Services\RepairService;
use App\Services\RepairStatusTokenGenerator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepairServiceTest extends TestCase
{
    private MockObject $connection;
    private MockObject $documents;
    private MockObject $repairs;
    private MockObject $events;
    private MockObject $branches;
    private MockObject $resolver;
    private MockObject $mailer;
    private RepairService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->method('transactional')->willReturnCallback(fn (callable $cb) => $cb($this->connection));
        $this->documents = $this->createMock(DocumentRepository::class);
        $this->repairs = $this->createMock(RepairRepository::class);
        $this->events = $this->createMock(RepairEventRepository::class);
        $this->branches = $this->createMock(BranchRepository::class);
        $this->resolver = $this->createMock(CustomerResolver::class);
        $this->mailer = $this->createMock(RepairMailer::class);

        $this->service = new RepairService(
            $this->connection,
            $this->documents,
            $this->repairs,
            $this->events,
            $this->branches,
            $this->resolver,
            $this->mailer,
            new RepairStatusTokenGenerator()
        );
    }

    private function form(array $override = []): array
    {
        return array_merge([
            'branch_id' => '1',
            'customer_name' => 'Hans Muster',
            'customer_phone' => '',
            'customer_email' => 'hans@example.com',
            'device_name' => 'iPhone 13',
            'imei_serial' => '',
            'damage_type' => 'screen_damage',
            'issue_text' => 'Display gebrochen',
            'work_text' => '',
            'price_chf' => '120,5',
            'due_date' => '2026-12-31',
            'accessories' => '',
            'unlock_code' => '1234',
        ], $override);
    }

    private function repair(string $status, ?string $unlock = '1234'): Repair
    {
        return new Repair(
            5, 10, $status, str_repeat('a', 32), 'iPhone 13', null, null, 'Defekt', null, null, null, null,
            $unlock, in_array($status, Repair::CLOSED_STATUSES, true) ? new \DateTimeImmutable() : null
        );
    }

    private function document(): Document
    {
        return new Document(Document::TYPE_REPAIR, 'RP-2026-000001', 1, 3, 'Hans Muster', null, 'hans@example.com', 10);
    }

    private function branch(): Branch
    {
        return $this->createMock(Branch::class);
    }

    private function expectLoaded(string $status, ?string $unlock = '1234'): void
    {
        $this->documents->method('findById')->with(10)->willReturn($this->document());
        $this->repairs->method('findByDocumentId')->with(10)->willReturn($this->repair($status, $unlock));
        $this->branches->method('findById')->willReturn($this->branch());
    }

    public function testCreateWritesReceivedEventAndSendsCreatedMail(): void
    {
        $this->documents->method('generateDocNumber')->willReturn('RP-2026-000001');
        $this->resolver->method('resolve')->willReturn(7);
        $this->documents->expects($this->once())->method('create')
            ->with($this->callback(fn (Document $d) => $d->getCustomerId() === 7
                && $d->getDocType() === Document::TYPE_REPAIR
                && $d->getBranchId() === 1))
            ->willReturn(10);
        $this->repairs->expects($this->once())->method('create')
            ->with($this->callback(fn (Repair $r) => $r->getStatus() === 'received'
                && preg_match('/^[a-f0-9]{32}$/', $r->getStatusToken()) === 1
                && $r->getPriceChf() === '120.50'
                && $r->getImeiSerial() === null
                && $r->getDueDate()?->format('Y-m-d') === '2026-12-31'))
            ->willReturn(5);
        $this->events->expects($this->once())->method('add')
            ->with($this->callback(fn (RepairEvent $e) => $e->getEventType() === 'status'
                && $e->getStatus() === 'received' && $e->getRepairId() === 5 && $e->getUserId() === 3));
        $this->branches->method('findById')->with(1)->willReturn($this->branch());
        $this->mailer->expects($this->once())->method('sendCreated')->willReturn(MailResult::sent());

        $result = $this->service->create($this->form(), null, 3);

        $this->assertSame(10, $result->documentId);
        $this->assertFalse($result->mailFailed());
    }

    public function testUpdateSetsResolvedCustomerId(): void
    {
        $this->expectLoaded('in_progress');
        $this->resolver->method('resolve')->willReturn(42);
        $this->documents->expects($this->once())->method('update');
        $this->documents->expects($this->once())->method('setCustomerId')->with(10, 42);
        $this->repairs->expects($this->once())->method('update')
            ->with($this->callback(fn (Repair $r) => $r->getStatus() === 'in_progress'
                && $r->getStatusToken() === str_repeat('a', 32) && $r->getUnlockCode() === '1234'));
        $this->events->expects($this->once())->method('add')
            ->with($this->callback(fn (RepairEvent $e) => $e->getEventType() === 'note'
                && $e->getMessage() === 'Angaben bearbeitet'));

        $this->service->update(10, $this->form(), null, 3);
    }

    public function testUpdateKeepsUnlockCodeNullWhenClosed(): void
    {
        $this->expectLoaded('picked_up', null);
        $this->resolver->method('resolve')->willReturn(42);
        $this->repairs->expects($this->once())->method('update')
            ->with($this->callback(fn (Repair $r) => $r->getUnlockCode() === null && $r->getClosedAt() !== null));

        $this->service->update(10, $this->form(), null, 3);
    }

    public function testStatusChangeWritesEvent(): void
    {
        $this->expectLoaded('received');
        $this->repairs->expects($this->once())->method('update')
            ->with($this->callback(fn (Repair $r) => $r->getStatus() === 'in_progress'));
        $this->events->expects($this->once())->method('add')
            ->with($this->callback(fn (RepairEvent $e) => $e->getStatus() === 'in_progress' && $e->getMessage() === 'Start'));
        $this->mailer->expects($this->never())->method('sendReady');

        $this->assertNull($this->service->changeStatus(10, 'in_progress', 'Start', 3));
    }

    public function testSameStatusDoesNothing(): void
    {
        $this->expectLoaded('in_progress');
        $this->repairs->expects($this->never())->method('update');
        $this->events->expects($this->never())->method('add');
        $this->mailer->expects($this->never())->method('sendReady');

        $this->assertNull($this->service->changeStatus(10, 'in_progress', null, 3));
    }

    public function testSameStatusWithNoteWritesNoteEventOnly(): void
    {
        $this->expectLoaded('in_progress');
        $this->repairs->expects($this->never())->method('update');
        $this->mailer->expects($this->never())->method('sendReady');
        $this->events->expects($this->once())->method('add')
            ->with($this->callback(fn (RepairEvent $e) => $e->getEventType() === RepairEvent::TYPE_NOTE
                && $e->getMessage() === 'Teil bestellt' && $e->getStatus() === null));

        $this->assertNull($this->service->changeStatus(10, 'in_progress', '  Teil bestellt ', 3));
    }

    public function testPickedUpClearsUnlockCode(): void
    {
        $this->expectLoaded('ready');
        $this->repairs->expects($this->once())->method('update')
            ->with($this->callback(fn (Repair $r) => $r->getUnlockCode() === null
                && $r->getClosedAt() !== null && $r->getStatus() === 'picked_up'));

        $this->service->changeStatus(10, 'picked_up', null, 3);
    }

    public function testReadySendsMailEachTimeItIsEntered(): void
    {
        $this->expectLoaded('in_progress');
        $this->mailer->expects($this->once())->method('sendReady')->willReturn(MailResult::sent());

        $result = $this->service->changeStatus(10, 'ready', null, 3);
        $this->assertTrue($result->isSent());
    }

    public function testLeavingReadySendsNoMail(): void
    {
        $this->expectLoaded('ready');
        $this->mailer->expects($this->never())->method('sendReady');

        $this->service->changeStatus(10, 'in_progress', null, 3);
    }

    public function testMailFailureDoesNotRollBack(): void
    {
        $this->expectLoaded('in_progress');
        $this->repairs->expects($this->once())->method('update');
        $this->mailer->method('sendReady')->willReturn(MailResult::failed('x'));

        $result = $this->service->changeStatus(10, 'ready', null, 3);

        $this->assertFalse($result->isSent());
        $this->assertSame('x', $result->error);
    }

    public function testInvalidStatusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeStatus(10, 'bogus', null, 3);
    }

    public function testResend(): void
    {
        $this->expectLoaded('ready');
        $this->mailer->expects($this->once())->method('sendCreated')->willReturn(MailResult::sent());
        $this->mailer->expects($this->once())->method('sendReady')->willReturn(MailResult::sent());

        $this->assertTrue($this->service->resend(10, 'created', 3)->isSent());
        $this->assertTrue($this->service->resend(10, 'ready', 3)->isSent());
    }

    public function testResendInvalidTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->resend(10, 'foo', 3);
    }
}
