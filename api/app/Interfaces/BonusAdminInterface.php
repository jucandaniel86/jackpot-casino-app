<?php

namespace App\Interfaces;

interface BonusAdminInterface
{
	public function listRules(array $params = []): array;

	public function getRule(int $id, ?string $forcedCasinoId = null): array;

	public function saveRule(array $params = [], ?int $adminId = null, ?string $forcedCasinoId = null): array;

	public function removeRule(int $id, ?string $forcedCasinoId = null): array;

	public function toggleRule(int $id, bool $isActive, ?int $adminId = null, ?string $forcedCasinoId = null): array;

	public function previewManual(array $params = [], ?string $forcedCasinoId = null): array;

	public function grantManual(array $params = [], ?int $adminId = null, ?string $forcedCasinoId = null): array;

	public function listGrants(array $params = [], ?string $forcedCasinoId = null): array;

	public function grantEvents(int $grantId, ?string $forcedCasinoId = null): array;

	public function stats(array $params = [], ?string $forcedCasinoId = null): array;

	public function runTestScenario(array $params = [], ?int $adminId = null, ?string $forcedCasinoId = null): array;

	public function listTestRuns(array $params = [], ?string $forcedCasinoId = null): array;

	public function getTestRun(int $id, ?string $forcedCasinoId = null): array;

	public function listTestRunLogs(int $runId, ?string $forcedCasinoId = null): array;
}
