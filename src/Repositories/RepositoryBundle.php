<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

final readonly class RepositoryBundle
{
    public function __construct(
        public UserRepositoryInterface $users,
        public GameRepositoryInterface $games,
        public PlayerRepositoryInterface $players,
        public PhaseRepositoryInterface $phases,
        public ActionRepositoryInterface $actions,
        public VoteRepositoryInterface $votes,
        public GameLogRepositoryInterface $logs,
        public ReportRepositoryInterface $reports,
        public TransactionManagerInterface $transactions,
    ) {
    }
}
