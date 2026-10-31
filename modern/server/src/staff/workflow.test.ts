import test from 'node:test';
import assert from 'node:assert/strict';
import { canMoveOrderStatus, nextMockupRevision, orderStatusAfterReview } from './workflow.js';

test('staff order state transitions allow only confirmed to processing to completed', () => {
  assert.equal(canMoveOrderStatus('CONFIRMED','PROCESSING'), true);
  assert.equal(canMoveOrderStatus('PROCESSING','COMPLETED'), true);
  assert.equal(canMoveOrderStatus('ORDER_PLACED','PROCESSING'), false);
  assert.equal(canMoveOrderStatus('DESIGN_REVIEW','CONFIRMED'), false);
  assert.equal(canMoveOrderStatus('COMPLETED','PROCESSING'), false);
});

test('mock-up publishing starts at revision one and only change requests permit a revision', () => {
  assert.equal(nextMockupRevision(null), 1);
  assert.equal(nextMockupRevision('CHANGES_REQUESTED', 3), 4);
  assert.equal(nextMockupRevision('READY_FOR_REVIEW'), null);
  assert.equal(nextMockupRevision('APPROVED'), null);
});

test('partial approval keeps design review open until every order item is approved', () => {
  assert.equal(orderStatusAfterReview('approve',['a','b'],['a']), 'DESIGN_REVIEW');
  assert.equal(orderStatusAfterReview('approve',['a','b'],['a','b']), 'CONFIRMED');
  assert.equal(orderStatusAfterReview('request_changes',['a'],[]), 'CHANGES_REQUESTED');
});
