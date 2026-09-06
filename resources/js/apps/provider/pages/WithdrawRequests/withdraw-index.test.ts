import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const src = readFileSync(join(__dirname, 'Index.tsx'), 'utf8');

describe('Withdraw Index redesign', () => {
  it('uses StackedDataTable instead of KTCard + legacy Table', () => {
    expect(src).toContain('StackedDataTable');
    expect(src).not.toContain('KTCard');
    expect(src).not.toMatch(/\bTable\b.*WithdrawRequest|from ['"]@\/shared\/components\/Table['"].*\n.*Table/);
    expect(src).not.toMatch(/<Table[\s>]/);
  });

  it('keeps Show / Delete row actions via LinkAction and ConfirmAction', () => {
    expect(src).toContain('LinkAction');
    expect(src).toContain('ConfirmAction');
    expect(src).toContain('WithdrawController.show');
    expect(src).toContain('WithdrawController.destroy');
    expect(src).toContain('ActionCell');
  });

  it('shows status and transfer_status together and uses formatListDate', () => {
    expect(src).toContain('row.status');
    expect(src).toContain('row.transfer_status');
    expect(src).toContain('formatListDate');
    expect(src).toContain('WithdrawTrigger');
  });
});
