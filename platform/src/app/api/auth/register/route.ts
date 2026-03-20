import { NextResponse } from 'next/server'
import { z } from 'zod'
import { db } from '@/db'
import { users } from '@/db/schema'
import { eq } from 'drizzle-orm'
import { hashPassword } from '@/lib/passwords'
import { randomBytes } from 'crypto'

const registerSchema = z.object({
  name: z.string().min(2).max(200),
  email: z.string().email(),
  password: z.string().min(8).max(128),
})

export async function POST(request: Request) {
  try {
    const body = await request.json()
    const parsed = registerSchema.safeParse(body)

    if (!parsed.success) {
      return NextResponse.json(
        { error: 'Dati non validi' },
        { status: 400 },
      )
    }

    const { name, email, password } = parsed.data

    // Verifica email non già registrata
    const existing = await db.query.users.findFirst({
      where: eq(users.email, email),
    })

    if (existing) {
      return NextResponse.json(
        { error: 'Email già registrata' },
        { status: 409 },
      )
    }

    // Crea utente
    const userId = randomBytes(16).toString('hex')
    const passwordHash = await hashPassword(password)

    await db.insert(users).values({
      id: userId,
      email,
      name,
      passwordHash,
      role: 'registered',
      isActive: 1,
      emailVerified: 0,
    })

    return NextResponse.json({ success: true, userId }, { status: 201 })
  } catch {
    return NextResponse.json(
      { error: 'Errore del server' },
      { status: 500 },
    )
  }
}
