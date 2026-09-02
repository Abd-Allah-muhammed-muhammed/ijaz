import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const showSrc = readFileSync(join(__dirname, 'Show.tsx'), 'utf8')

describe('Withdraw Show payment-card cleanup', () => {
  it('Withdraw Show no longer imports or references BankCardBootstrap/paymentResponse', () => {
    expect(showSrc).not.toMatch(/BankCardBootstrap/)
    expect(showSrc).not.toMatch(/paymentResponse/)
    expect(showSrc).not.toMatch(/PaymentResponse/)
  })
})
