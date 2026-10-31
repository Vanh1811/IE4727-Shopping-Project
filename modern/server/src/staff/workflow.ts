export type OrderWorkflowStatus = 'ORDER_PLACED'|'DESIGN_REVIEW'|'CHANGES_REQUESTED'|'CONFIRMED'|'PROCESSING'|'COMPLETED';
export type MockupWorkflowStatus = 'READY_FOR_REVIEW'|'CHANGES_REQUESTED'|'APPROVED';

export function canMoveOrderStatus(current: string, next: string): next is 'PROCESSING'|'COMPLETED' {
  return (current === 'CONFIRMED' && next === 'PROCESSING') || (current === 'PROCESSING' && next === 'COMPLETED');
}

export function nextMockupRevision(current: MockupWorkflowStatus | null, revision = 0) {
  if (current === null) return 1;
  if (current === 'CHANGES_REQUESTED') return revision + 1;
  return null;
}

export function orderStatusAfterReview(decision: 'approve'|'request_changes', orderItemIds: string[], approvedItemIds: string[]) {
  if (decision === 'request_changes') return 'CHANGES_REQUESTED' as const;
  return orderItemIds.length > 0 && orderItemIds.every((id) => approvedItemIds.includes(id)) ? 'CONFIRMED' as const : 'DESIGN_REVIEW' as const;
}
