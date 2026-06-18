export interface Role {
  id: number;
  name: string;
  guard_name: string;
}

export interface Feature {
  id: number;
  name: string;
  slug: string;
  type: 'boolean' | 'consumable' | 'limit';
  description: string | null;
  default_value: string | null;
  resettable_period: string;
  pivot?: {
    plan_id: number;
    feature_id: number;
    value: string;
  };
}

export interface User {
  id: number;
  name: string;
  email: string;
  phone_number: string | null;
  email_verified_at: string | null;
  phone_verified_at: string | null;
  is_suspended: boolean;
  avatar_url: string;
  referral_code: string | null;
  locale: string;
  terms_accepted_at: string | null;
  email_bounced_at: string | null;
  roles: Role[];
  active_subscription: Subscription | null;
  last_login_at: string | null;
}

export interface Plan {
  id: number;
  name: string;
  slug: string;
  price: number;
  currency: string;
  billing_period: 'month' | 'year' | 'lifetime';
  trial_days: number;
  grace_days: number;
  is_active: boolean;
  sort_order: number;
  features: Feature[];
  metadata: Record<string, string> | null;
}

export interface Subscription {
  id: number;
  status: SubscriptionStatus;
  plan: Plan;
  stripe_id: string | null;
  trial_ends_at: string | null;
  ends_at: string | null;
  grace_ends_at: string | null;
  cancels_at: string | null;
  auto_renew: boolean;
  canceled_at: string | null;
  is_valid: boolean;
  is_lifetime: boolean;
  days_remaining: number | null;
}

export type SubscriptionStatus = 'active' | 'trialing' | 'grace' | 'canceled' | 'expired' | 'paused';

export interface FeatureUsage {
  slug: string;
  name: string;
  used: number;
  limit: number | 'unlimited';
  percentage: number;
  remaining: number | 'unlimited';
  resettable_period: string;
}

export interface UserNotification {
  id: number;
  type: string;
  title: string;
  body: string;
  action_url: string | null;
  read_at: string | null;
  created_at: string;
}

export interface OnboardingStep {
  key: string;
  label: string;
  done: boolean;
  action_url: string;
}

export interface Branding {
  appName: string;
  logoUrl: string;
  faviconUrl: string;
  primaryColor: string;
  supportEmail: string | null;
  footerText: string | null;
}
