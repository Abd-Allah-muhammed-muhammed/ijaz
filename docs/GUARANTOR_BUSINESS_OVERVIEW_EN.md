# Guarantor — Business overview

This document explains the Guarantor feature in plain language for product, support, testers, and other stakeholders. It is not a technical guide.

---

## 1. What is a Guarantor Request?

A guarantor request is the platform acting as a trusted middleman for a deal.

One party is doing the work. The other is paying. Instead of handing money over directly and hoping everything goes well, both sides agree that **the platform holds the money until the work is confirmed complete**. If the deal finishes successfully, the money is released to the person who did the work. If the deal is cancelled before that, the money is not meant to stay with the worker.

It is the same idea as a lawyer holding funds in escrow until a house sale closes: nobody gets paid until both sides (or the process) confirm it is time.

---

## 2. Who's involved

There is no separate “guarantor company” login. “Company” is a **type of deal**, not a third person.

| Role | Who they are in real life | What they are responsible for |
|---|---|---|
| **The service provider (the requester)** | The business or professional who starts the guarantee | Describes the deal, uploads the required documents and signature, waits for admin review, then waits for the other party. They receive the money when the deal is completed. They can close the deal once money is already being held. While the request is still waiting for admin, they can still edit or withdraw it. |
| **The customer (the other party / the payer)** | A registered user on the platform, identified by their phone number | Reviews the request after admin approval, **accepts or declines** it, then **pays**. They can also close the deal once money is being held. They cannot create this kind of request in the customer app — paying is their role. |
| **Admin (platform staff)** | Staff using the admin dashboard | Reviews every new request and approves or rejects it. Later they can cancel a deal, and on company deals they can release an installment that is being held. The two parties cannot do admin’s job from the apps. |

The other party must already have a user account. You cannot name yourself as the other party, and you cannot name a phone that is not registered as a user.

---

## 3. The two types

### Individual — one payment, one release

**Example:** Ahmed is a finishing contractor. Sara wants him to renovate her apartment for **5,000 SAR**. They do not want to rely on a handshake.

Ahmed opens a **one-time (Individual)** guarantee for Sara. After the platform reviews it and Sara accepts, she pays the full amount in one go (the agreed 5,000 SAR plus a small platform fee, currently **10 SAR**, so she pays **5,010 SAR**). The platform holds that money while Ahmed does the work. When they confirm the job is done, the deal is closed and Ahmed receives the agreed amount.

Use Individual when there is a **single payment**, not a schedule.

### Company — a payment plan, released step by step

**Example:** Al-Noor Contracting agrees with Khalid on a villa finishing project worth **30,000 SAR**, paid in **three installments of 10,000 SAR** each (for example due in 30, 60, and 90 days).

Al-Noor opens a **Company** guarantee: company details, commercial registration, the authorised signatory, whether they are acting under a power of attorney or an agency, bank details, contracts, and the installment plan. After admin approval and Khalid’s acceptance, Khalid pays **one installment at a time**, in order. Each payment is held. Paying the **next** installment is what typically **releases the previous** one to Al-Noor.

Use Company when the deal is a **project with a payment schedule** (up to twelve installments). The installment amounts must add up to the full contract amount.

---

## 4. The journey, step by step

The path below is the same idea for both types. Differences are called out where they matter. We follow Ahmed & Sara (Individual, 5,000 SAR) and, where the money works differently, Al-Noor & Khalid (Company, 30,000 SAR).

### Step 1 — The provider creates the request

Ahmed fills in the deal: who Sara is (her registered phone), the amount, a short description, and his signature. Al-Noor does the same for a company deal, plus company papers and the installment calendar.

**What happens:** the request is submitted and sits with **admin**. It is **not** yet Sara’s or Khalid’s turn. They are **not** notified at this moment.

**Who is waiting:** Ahmed / Al-Noor, waiting for admin.

**What they can do:** the provider can still **edit or withdraw** the request. The customer cannot accept, pay, or chat yet.

**Notifications:** the provider is told the request was submitted and is waiting for review. Admins who handle guarantor requests are told a new request needs review. The customer hears nothing yet.

### Step 2 — Admin reviews

Admin checks the details and documents.

**If admin rejects:** the request is closed. Ahmed is notified that admin rejected it. Sara was never asked to do anything. No money has moved. This is the end of the story (see [section 7](#7-what-happens-if-something-goes-wrong)).

**If admin approves:** it becomes the **customer’s turn**.

**Notifications on approval:** **both** Ahmed and Sara (or Al-Noor and Khalid) are told that admin approved and that the other party needs to respond.

### Step 3 — The customer accepts or declines

Sara sees a decision screen: accept or reject. Rejecting requires a reason.

**If she rejects:** the request is closed. Ahmed is notified. No payment, no chat. This is the end (see [section 7](#7-what-happens-if-something-goes-wrong)).

**If she accepts:** the deal is on. **Chat opens** so they can talk about the work. Sara is **not** sent a “you accepted” message — she already knows. Ahmed **is** notified that she accepted.

**Important:** they still **cannot close (end)** the deal at this point. Ending is only allowed after money is actually being held in a way the platform treats as “work in progress” or “overdue” (explained below).

### Step 4 — Payment

**Individual (Sara):** she pays the **full amount plus the platform fee** in one checkout (5,010 SAR). After the bank payment succeeds, the deal moves to **in progress**. The pay button goes away. Chat stays available. **Either party can now end** the deal when the work is done.

There is **no** “payment received” notification today. After paying, they should open the request again to see that it has moved on. If it still looks unpaid, the payment may not have finished yet.

**Company (Khalid):** he pays **installment 1 only** (10,000 SAR — the fee is **not** added on top of each installment). After that:

- The installment shows as **paid** (held by the platform).
- The **overall deal often still says “accepted”** — it does **not** switch to “in progress” just because the first installment was paid. That is expected, not a bug.
- Chat is available.
- **Ending is still not allowed** while the deal is in this “accepted” state, even if some installments are already paid.

He then pays installment 2, then 3, always in order. Paying 2 typically **releases** installment 1 to Al-Noor (they get a “funds released” notice). Paying 3 releases 2. When the deal is later ended, the **last paid** installment is released.

If a company deal had already become **overdue** and then Khalid pays, the deal can move to **in progress** and the late mark is cleared.

### Step 5 — Work happens (chat is on)

While the deal is accepted (company, after early payments), in progress, or overdue, both sides can message each other. Chat is **not** offered while waiting for admin, or while waiting for the customer to accept.

### Step 6 — Closing the deal (ending it)

When the work is done, **either party** can end the request — but **only** if the deal is **in progress** or **overdue**.

- **Individual:** ending settles the held money to Ahmed (the agreed amount; the platform keeps the fee).
- **Company:** ending releases the latest paid installment (if any) to Al-Noor.

**Both parties** are notified that the request has been **ended**.

They should not be offered “open chat” from a closed request. The story is finished.

---

## 5. Company installments explained simply

Think of the 30,000 SAR as three locked boxes. Khalid can only open box 1 first. Opening box 2 is what unlocks box 1 for Al-Noor. Opening box 3 unlocks box 2. Ending the deal unlocks the last box that was already paid.

**Rules in everyday terms:**

- Only Khalid (the customer) pays — never the company.
- He must pay in order. He cannot skip to installment 3.
- He cannot pay the same installment twice.
- Each checkout is that installment’s amount only.

### A simple late-payment story

Installment 1 was due on **1 March**. Khalid has not paid yet.

| When | What happens |
|---|---|
| **The due date itself (1 March)** | Nothing special. The installment is still unpaid. |
| **Day 1 after the due date (2 March)** — and day 2 | Khalid gets a **reminder** that a payment is due. Al-Noor is not notified yet. The overall deal is **not** marked late. He can still pay. |
| **Day 3 after the due date (4 March)** | The **whole deal is marked overdue**. **Both** Khalid and Al-Noor are told an installment is overdue. The installment itself still shows as **unpaid** (not a separate “overdue installment” label). He can still pay. Either party can now **end** the deal because it is overdue. |
| **Day 14 after the due date** | If that installment **was paid** and the money is still being held, the platform **automatically releases** it to Al-Noor, and Al-Noor is notified. If it is **still unpaid**, the system does **not** cancel the deal and does **not** invent an extra notice — it can simply remain overdue. |

Reminders can repeat on later days for the same unpaid installment. An unpaid late installment is **not** auto-cancelled.

---

## 6. How the money actually works

Imagine a sealed envelope the platform holds on behalf of both sides.

1. **The customer pays** (once for Individual; per installment for Company) through the platform’s payment page.
2. **The platform holds the money.** Ahmed / Al-Noor cannot spend it yet. Sara / Khalid cannot take it back from the app as if the deal were finished.
3. **Release (the envelope is opened for the provider):**
   - Individual: when the deal is **ended** successfully.
   - Company: typically when the **next** installment is paid (that unlocks the previous one); or when the deal is **ended** (last paid installment); or, if an installment was already paid, automatically **14 days after its due date**.
4. **If admin cancels** after money was paid: the platform **undoes the internal hold**. That does **not** automatically send the money back to the customer’s **bank card**. Finance / support must handle a card refund manually until a card-refund process with the payment provider is in place.

The customer always pays. The provider always receives on release. A company field such as “power of attorney” vs “agency” is only paperwork about **who signed** — it does **not** change who pays.

**Fees:** the platform adds a small fee at creation (currently **10 SAR**). On an Individual deal, the customer pays amount + fee in one checkout. On a Company deal, each installment checkout is the installment amount only.

---

## 7. What happens if something goes wrong

| What happened | What it means for the provider | What it means for the customer | Money | Can they continue? |
|---|---|---|---|---|
| **Admin rejects** the request | Notified. The deal never started. | Usually never asked to act; they may never have been notified. | Nothing was paid. | No. Closed. |
| **The customer rejects** after admin approved | Notified that the other party declined (a reason was required). | They chose to walk away. | Nothing was paid (payment only happens after accept). | No. Closed. |
| **Admin cancels** later | Both parties are notified that the request was **cancelled** (this is a different message from “ended”). | Same. | Internal holds are reversed on the platform. **Card refund is not automatic.** | No. Closed. Only admin can cancel — the two parties cannot cancel from the apps. |
| **The deal is ended** (success) | Both notified that it **ended**. They receive the released funds as described above. | Same notification. They have paid; the work is treated as complete. | Individual: held funds go to the provider. Company: last paid installment is released. | No. Closed. |
| **Payment is late (Company)** | From day 3 they are told it is overdue. They can still wait for payment, or end the deal. | Reminded from day 1; from day 3 the deal is overdue. They can still pay, or end. | Unpaid money was never captured for that installment. Already-paid installments stay held until release rules apply. | Yes, until someone ends it or admin cancels. |

Rejected and cancelled deals are finished. The parties should not be invited to accept, pay, or start chat from that request.

---

## 8. Current known limitations

These are product facts support and stakeholders should know **today**:

1. **Card refunds are not automatic.** If a deal is cancelled after the customer paid, the money is **not** sent back to their bank card by itself. That needs **manual handling** until a refund process with the payment provider (currently Al Rajhi) is added. Do not promise “you will be refunded to your card.”

2. **Customers should not create guarantor requests.** Creating a guarantee is the **provider’s** job. The customer’s job is to review, accept or decline, and pay.

3. **There is no “we received your payment” notification.** After checkout, the person should reopen the request. Individual deals should then show as in progress. Company deals should show that installment as paid — the overall deal may still say accepted.

4. **The two parties cannot cancel.** Only admin can cancel. If a customer asks “how do I cancel?”, the honest answer is: they cannot from the app; they need support / admin.

5. **A company deal cannot be ended while it still shows as accepted**, even if installments have already been paid. Ending is only possible when the deal is **in progress** or **overdue**. That surprises people after the first company payment.

6. **A late installment still looks unpaid** on the installment itself. Lateness is shown on the **deal** (overdue) and as “this payment date has passed.” Do not wait for the installment row to change to a special overdue label.

7. **Admin review can take time.** New requests wait in the admin queue. If nothing has moved, check whether admin has reviewed it — it is not necessarily an app problem.

8. **The customer is not told when the request is first created.** They are told when **admin approves**. If they say “I never got a message,” that is expected until approval.

9. **Phone push notifications go to users (and to admins for new reviews).** Service providers see updates **in the app**, but they do not get the same phone push as users for these events.

10. **The platform fee is not chosen by the user** (currently 10 SAR). Individual checkout includes it; company installment checkout does not add it on top of each installment.

11. **Company deals do not collect a long description** at creation. The project type is what people will see as the title.

---

## 9. Common questions / troubleshooting for support staff

**Why can’t the Provider pay?**  
Because the provider is not the payer. The **customer** (the other party named on the request) always pays. After they accept, they see pay (full amount on Individual, next installment on Company). The provider waits to receive money when it is released.

**Why does the company deal still look like it isn’t progressing after the first payment?**  
That is expected. Paying the first installment marks **that installment** as paid, but the **overall deal often stays “accepted.”** It usually becomes “in progress” only after the deal was overdue and then a payment is made. To see progress, look at the **installment list** (unpaid → paid → released), not only the deal’s main label.

**What does “authorization type” mean, and does it affect who pays?**  
It is company paperwork: whether the person who signed is acting under a **power of attorney** or an **agency**. It is collected once when the company request is created. **It does not affect who pays.** Payment is always the customer. Choosing one or the other does not let the provider pay.

**Why can’t they end the deal even though money was paid (Company)?**  
If the deal still says **accepted**, ending is blocked on purpose — even with paid installments. Ending is allowed only in **in progress** or **overdue**. Individual deals can be ended after the single payment succeeds (in progress).

**Why can’t the customer accept yet?**  
Either admin has not approved, or admin rejected it. Accept/reject is only after **admin approval**. Chat and pay are not available before the customer accepts.

**The customer says they never heard about the request.**  
They are not notified at creation. They are notified when admin **approves**. Until then, only the provider (and admin) know.

**They paid but nothing changed.**  
There is no payment-success notification. Ask them to reopen the request. If checkout did not finish, the deal will still look unpaid. Individual should move to in progress after a successful payment; company should show the installment as paid.

**They want a refund to the card after cancel.**  
Cancellation does not refund the card automatically. Escalate to finance / admin for a **manual** refund. Do not confirm that the card was already credited.

**Can they skip an installment or pay out of order?**  
No. Each installment requires the previous one to be paid (or already released).

**Chat is missing.**  
Chat starts only after the customer **accepts**, and continues while the deal is accepted, in progress, or overdue. It is not available while waiting for admin, waiting for accept, or after the deal is closed (rejected, ended, cancelled).

**Who can close a deal?**  
- **End** (successful close): either party, only when in progress or overdue.  
- **Cancel**: admin only.  
- **Reject**: admin (during review) or the customer (after approval, instead of accepting).
