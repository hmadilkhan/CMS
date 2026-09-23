# Sales Partner Organization Override

## What you asked for

An override that can be set on the sales partner organization itself, not just on
individual sales people, and that the organization's own Sales Manager cannot see
anywhere. Only Admins.

## How the override works today

At the moment the override is set per sales person in User Management. When a deal
is created, that person's override is copied onto the project and added into the
redline cost, which then feeds the commission figure.

The Override Report already shows this. A Super Admin sees every partner, everyone
else only sees their own organization, so a Sales Manager can already see the
per-person overrides for his own org. That was intentional and I am leaving it as is.

## What I will build

Two new fields, Base Price and Panel Price, on the Sales Partner screen under
Operations, visible only to Admins. When a deal is created for that organization the
override is applied on the server, read directly from the organization record. It is
never sent to the browser, so it cannot be read off the page or changed by whoever is
filling the form. It is also stored separately from the sales person's override, so
the two never get mixed up and the existing reports keep showing exactly what they
show today.

## Keeping it hidden

Hiding the field itself is the easy part. The real work is the places where the
amount can be worked out indirectly, and I will be covering all of them:

- **Override Report** (screen, Excel and PDF): the org columns only appear for Admins.
- **Project > Financial Details**: Redline Cost and Commission on that tab are already
  shown with the override baked in. For non-Admins these will keep using only the
  sales person override, otherwise the org amount could be worked out by subtraction.
- **Project Acceptance PDF**: this one is emailed to the sales partner, so the org
  override stays out of it completely.
- **Custom Report Builder**: new fields gated the same way.
- **AI Assistant**: the new fields marked as restricted so it refuses them for anyone
  who is not an Admin.

## One thing I noticed while looking into this

On the current intake form the sales person's override is sent to the browser as a
hidden field, which means it can be read and edited from the page before the deal is
saved. I will move that to the server in the same pass so both overrides work the same
safe way.

## Need your confirmation on three points

1. Does the org override add on top of the sales person's override, or replace it?
   My assumption is that it adds on top.
2. Should it change the redline cost and commission the way the current override does,
   or is it only a figure for Admin reporting? My assumption is that it changes them.
3. By "Admins" do you mean Super Admin only, or the Admin role as well?

Once these three are confirmed I will start on it and give you a timeline.
